<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class SubscriptionService
{
    /**
     * Check if a shop currently has an active, valid subscription (Free or Pro).
     */
    public function isActive(ShopifyStore|int $shop): bool
    {
        $shopId = $this->resolveShopId($shop);

        if (! $shopId) {
            return false;
        }

        $this->expireOverdueSubscriptions($shopId);

        $now = now();

        return ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where(function ($query) use ($now): void {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', $now);
            })
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Check if a shop currently has an active Pro (Premium) subscription.
     */
    public function isPro(ShopifyStore|int $shop): bool
    {
        $activeSubscription = $this->getActiveSubscription($shop);

        return $activeSubscription !== null && $activeSubscription->plan?->isPro() === true;
    }

    /**
     * Check if a shop currently has an active Free subscription.
     */
    public function isFree(ShopifyStore|int $shop): bool
    {
        $activeSubscription = $this->getActiveSubscription($shop);

        return $activeSubscription !== null && $activeSubscription->plan?->isFree() === true;
    }

    /**
     * Get the currently active subscription for a shop.
     * Prioritizes active Pro subscription over Free subscription if both exist.
     */
    public function getActiveSubscription(ShopifyStore|int $shop): ?ShopSubscription
    {
        $shopId = $this->resolveShopId($shop);

        if (! $shopId) {
            return null;
        }

        $this->expireOverdueSubscriptions($shopId);

        $now = now();

        // 1. Check for active Pro subscription first
        $activePro = ShopSubscription::with('plan')
            ->where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_start', '<=', $now)
            ->where('current_period_end', '>', $now)
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)->where('price', '>', 0);
            })
            ->latest('id')
            ->first();

        if ($activePro) {
            return $activePro;
        }

        // 2. Check for permanent or active Free subscription
        $activeFree = ShopSubscription::with('plan')
            ->where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where(function ($query) use ($now): void {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', $now);
            })
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)->where(function ($q): void {
                    $q->where('slug', 'free')->orWhere('price', '<=', 0);
                });
            })
            ->latest('id')
            ->first();

        if ($activeFree) {
            return $activeFree;
        }

        return null;
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

        $this->expireOverdueSubscriptions($shopId);

        return ShopSubscription::with('plan')
            ->where('shop_id', $shopId)
            ->latest('id')
            ->first();
    }

    /**
     * Idempotently assign permanent Free subscription to a store.
     */
    public function assignFreeSubscription(ShopifyStore|int $shop): ShopSubscription
    {
        $store = $shop instanceof ShopifyStore ? $shop : ShopifyStore::findOrFail($shop);

        return DB::transaction(function () use ($store): ShopSubscription {
            $freePlan = Plan::where('slug', 'free')->where('is_active', true)->first()
                ?? Plan::where('price', '<=', 0)->where('is_active', true)->first()
                ?? Plan::where('slug', 'free')->first()
                ?? Plan::where('price', '<=', 0)->firstOrFail();

            // Check if store already has a valid active subscription
            $existingActive = ShopSubscription::with('plan')
                ->where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->where(function ($q): void {
                    $q->whereNull('current_period_end')
                        ->orWhere('current_period_end', '>', now());
                })
                ->whereHas('plan', fn ($q) => $q->where('is_active', true))
                ->latest('id')
                ->first();

            if ($existingActive) {
                return $existingActive;
            }

            // Create permanent Free subscription
            $subscription = ShopSubscription::create([
                'shop_id' => $store->id,
                'plan_id' => $freePlan->id,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'activated_at' => now(),
                'current_period_start' => now(),
                'current_period_end' => null,
                'metadata' => ['source' => 'auto_free_assignment'],
            ]);

            $store->update(['plan_name' => $freePlan->name]);

            Log::info('Assigned permanent Free subscription to store', [
                'shop' => $store->shop,
                'subscription_id' => $subscription->id,
            ]);

            return $subscription->load('plan');
        });
    }

    /**
     * Activate or renew a Pro subscription for a shop.
     */
    public function activateSubscription(
        ShopifyStore $store,
        Plan $plan,
        ?string $chargeId = null,
        array $metadata = []
    ): ShopSubscription {
        if (! $plan->is_active || $plan->isFree()) {
            throw new InvalidArgumentException('Only the active SocialNest Pro plan can be subscribed to.');
        }

        return DB::transaction(function () use ($store, $plan, $chargeId, $metadata): ShopSubscription {
            $now = now();
            $start = $now;
            $end = $now->copy()->addDays(30);

            // Expire all existing active subscriptions for this store
            ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

            // Create new subscription record with exactly 30 days duration
            $subscription = ShopSubscription::create([
                'shop_id' => $store->id,
                'plan_id' => $plan->id,
                'shopify_charge_id' => $chargeId,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'activated_at' => $now,
                'current_period_start' => $start,
                'current_period_end' => $end,
                'metadata' => ! empty($metadata) ? $metadata : null,
            ]);

            // Synchronize store's plan_name
            $store->update(['plan_name' => $plan->name]);

            Log::info('Activated Pro subscription for store', [
                'shop' => $store->shop,
                'subscription_id' => $subscription->id,
                'shopify_charge_id' => $chargeId,
            ]);

            return $subscription->load('plan');
        });
    }

    /**
     * Cancel an active subscription immediately and cleanly revert to permanent Free.
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

            // Unlink store from active plan name without creating a ghost free subscription
            $store->update(['plan_name' => null]);

            Log::info('Cancelled active subscription and cleared active plan assignment', [
                'shop' => $store->shop,
                'cancelled_subscription_id' => $activeSubscription->id,
            ]);

            return $activeSubscription->fresh('plan');
        });
    }

    /**
     * Helper to expire overdue subscriptions that have an end date.
     */
    protected function expireOverdueSubscriptions(int $shopId): void
    {
        $now = now();

        // Expire paid subscriptions that have reached or passed current_period_end
        $expiredCount = ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', $now)
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        // Expire subscriptions whose plans have been deactivated
        ShopSubscription::where('shop_id', $shopId)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', false);
            })
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} overdue subscription(s) for shop ID {$shopId}");
        }
    }

    /**
     * Helper to resolve integer store ID.
     */
    protected function resolveShopId(ShopifyStore|int $shop): int
    {
        return $shop instanceof ShopifyStore ? (int) $shop->id : (int) $shop;
    }
}
