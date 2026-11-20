<?php

namespace App\Console\Commands;

use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Services\ShopifyPricingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shopify:reconcile-subscriptions {--shop= : Specific shop domain to reconcile}')]
#[Description('Reconcile active subscriptions against the Shopify Partner API')]
class ReconcileShopifySubscriptionsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ShopifyPricingService $pricingService): int
    {
        if (! $pricingService->isPartnerApiConfigured()) {
            $missing = $pricingService->getMissingConfigurationDetails();
            $this->warn('Shopify Partner API is not configured. Missing: '.implode(', ', $missing));

            return self::SUCCESS;
        }

        $shopDomain = $this->option('shop');

        if ($shopDomain) {
            $store = ShopifyStore::where('shop', $shopDomain)->first();
            if (! $store) {
                $this->error("Shop '{$shopDomain}' not found.");

                return self::FAILURE;
            }
            $stores = collect([$store]);
        } else {
            $stores = ShopifyStore::where('is_active', true)
                ->whereNotNull('access_token')
                ->get();
        }

        if ($stores->isEmpty()) {
            $this->info('No active stores found to reconcile.');

            return self::SUCCESS;
        }

        $this->info("Reconciling subscriptions for {$stores->count()} store(s)...");

        $stats = [
            'active' => 0,
            'cancelled' => 0,
            'frozen' => 0,
            'skipped' => 0,
            'unchanged' => 0,
        ];

        foreach ($stores as $store) {
            $this->line("Reconciling store: {$store->shop}...");

            $activeSub = $store->activeSubscription;

            // Invalidate cache before running reconciliation
            $pricingService->invalidateStoreSubscriptionCache($store);

            $partnerResult = $pricingService->queryPartnerActiveSubscription($store);

            if (isset($partnerResult['error'])) {
                $this->warn("Skipping {$store->shop}: {$partnerResult['error']}");
                $stats['skipped']++;

                continue;
            }

            $partnerSub = $partnerResult['subscription'] ?? null;

            if ($partnerSub === null) {
                // No active subscription on Partner API
                if ($activeSub && ($activeSub->isVerified() || $activeSub->plan_handle !== null)) {
                    $activeSub->update([
                        'status' => ShopSubscription::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                    ]);
                    $pricingService->invalidateStoreSubscriptionCache($store);

                    $this->warn('  -> Active subscription cancelled (no active plan on Shopify). Data retained, store downgraded to free tier.');
                    $stats['cancelled']++;
                } else {
                    $stats['unchanged']++;
                }

                continue;
            }

            // Check if Partner API reports frozen status
            $partnerStatus = strtoupper((string) ($partnerSub['status'] ?? 'ACTIVE'));
            if ($partnerStatus === 'FROZEN') {
                if ($activeSub) {
                    $activeSub->update([
                        'status' => ShopSubscription::STATUS_FROZEN,
                    ]);
                }
                $pricingService->invalidateStoreSubscriptionCache($store);
                $this->warn('  -> Subscription marked as frozen.');
                $stats['frozen']++;

                continue;
            }

            // Sync active verified subscription
            $verified = $pricingService->verifyAndSyncActiveSubscription($store);
            if ($verified && $verified->isActive() && $verified->isVerified()) {
                $this->info("  -> Active subscription verified: plan '{$verified->plan_handle}'");
                $stats['active']++;
            } else {
                $this->warn("  -> Could not verify subscription for {$store->shop}");
                $stats['skipped']++;
            }
        }

        $this->info("Reconciliation complete. Active: {$stats['active']}, Cancelled: {$stats['cancelled']}, Frozen: {$stats['frozen']}, Skipped: {$stats['skipped']}, Unchanged: {$stats['unchanged']}.");

        return self::SUCCESS;
    }
}
