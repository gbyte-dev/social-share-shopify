<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SubscriptionService
{
    /**
     * Check if a shop currently has an active, valid SocialNest Pro subscription.
     *
     * A subscription is active only when:
     * - A valid subscription exists for the shop.
     * - Status is 'active'.
     * - Plan is active and paid (SocialNest Pro).
     * - current_period_start is not in the future (<= now()).
     * - current_period_end is strictly greater than current time (> now()).
     *
     * Also updates any expired subscription status to 'expired' and invalidates Free plan subscriptions.
     */
    public function isActive(ShopifyStore|int $shop): bool
    {
        $shopId = $this->resolveShopId($shop);

        if (! $shopId) {
            return false;
        }

        $now = now();

        // Expire active subscriptions that have reached or passed current_period_end
        ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_end', '<=', $now)
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        // Expire any subscriptions associated with Free or inactive plans
        ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', false)->orWhere('price', '<=', 0);
            })
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        // Query for currently active paid subscription
        return ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_start', '<=', $now)
            ->where('current_period_end', '>', $now)
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)->where('price', '>', 0);
            })
            ->exists();
    }

    /**
     * Get the currently active subscription for a shop.
     */
    public function getActiveSubscription(ShopifyStore|int $shop): ?ShopSubscription
    {
        $shopId = $this->resolveShopId($shop);

        if (! $shopId) {
            return null;
        }

        $now = now();

        // Expire subscriptions that passed current_period_end
        ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_end', '<=', $now)
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        // Expire any subscriptions associated with Free or inactive plans
        ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', false)->orWhere('price', '<=', 0);
            })
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        return ShopSubscription::with('plan')
            ->where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_start', '<=', $now)
            ->where('current_period_end', '>', $now)
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)->where('price', '>', 0);
            })
            ->latest('id')
            ->first();
    }

    /**
     * Get the latest subscription record for a shop regardless of active status.
     */
    public function getLatestSubscription(ShopifyStore|int $shop): ?ShopSubscription
    {
        $shopId = $this->resolveShopId($shop);

        if (! $shopId) {
            return null;
        }

        $now = now();

        // Expire any subscriptions that passed current_period_end
        ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_end', '<=', $now)
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        return ShopSubscription::with('plan')
            ->where('shop_id', $shopId)
            ->latest('id')
            ->first();
    }

    /**
     * Activate or manually renew a dummy 30-day subscription for a shop.
     *
     * Subscription rules:
     * 1. Only active, paid plans (SocialNest Pro) can be activated.
     * 2. On activation, set status to 'active'.
     * 3. Set activated_at to current timestamp.
     * 4. Set current_period_start to activation timestamp (now).
     * 5. Set current_period_end to exactly 30 days after current_period_start.
     * 6. No automatic renewal.
     * 7. Preserves historical records (does not delete previous subscriptions).
     * 8. Ensures only one active subscription exists per shop.
     * 9. Uses database transaction.
     */
    public function activateSubscription(ShopifyStore $store, Plan $plan): ShopSubscription
    {
        if (! $plan->is_active || $plan->isFree()) {
            throw new InvalidArgumentException('Only the active SocialNest Pro plan can be subscribed to.');
        }

        return DB::transaction(function () use ($store, $plan): ShopSubscription {
            $now = now();
            $start = $now;
            $end = $now->copy()->addDays(30);

            // Mark all existing active subscriptions for this store as expired
            ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

            // Create new subscription record with exactly 30 days duration
            $subscription = ShopSubscription::create([
                'shop_id' => $store->id,
                'plan_id' => $plan->id,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'activated_at' => $now,
                'current_period_start' => $start,
                'current_period_end' => $end,
            ]);

            // Synchronize store's plan_name
            $store->update(['plan_name' => $plan->name]);

            return $subscription->load('plan');
        });
    }

    /**
     * Cancel an active subscription immediately.
     *
     * All feature and storefront access is blocked instantly.
     */
    public function cancelSubscription(ShopifyStore $store): ShopSubscription
    {
        return DB::transaction(function () use ($store): ShopSubscription {
            $activeSubscription = $this->getActiveSubscription($store);

            if (! $activeSubscription) {
                throw new RuntimeException('No active subscription found to cancel.');
            }

            $activeSubscription->update([
                'status' => ShopSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_at_period_end' => false,
            ]);

            return $activeSubscription->fresh('plan');
        });
    }

    /**
     * Helper to resolve integer store ID.
     */
    protected function resolveShopId(ShopifyStore|int $shop): int
    {
        return $shop instanceof ShopifyStore ? (int) $shop->id : (int) $shop;
    }
}
