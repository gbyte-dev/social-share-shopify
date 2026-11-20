<?php

namespace App\Http\Controllers;

use App\Models\PendingShopRedaction;
use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Models\WishlistItem;
use App\Services\ShopifyPricingService;
use App\Services\ShopifyService;
use App\Services\ShopRedactionService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopifyWebhookController extends Controller
{
    public function __construct(
        protected ShopifyService $shopifyService,
        protected ShopRedactionService $shopRedactionService
    ) {}

    /**
     * Process incoming webhooks from Shopify.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        // Verify HMAC signature before any processing or deletion
        if (! $this->shopifyService->verifyWebhookHmac($rawPayload, $hmacHeader)) {
            Log::warning('Rejected Shopify webhook with invalid HMAC signature', [
                'topic' => $topic,
                'shop' => $shopDomain,
            ]);

            return response()->json(['error' => 'Invalid HMAC signature'], 401);
        }

        $canonicalShop = $this->shopifyService->normalizeShopDomain($shopDomain);
        $data = json_decode($rawPayload, true) ?? [];

        Log::info("Shopify webhook received: {$topic}", [
            'shop' => $canonicalShop,
            'topic' => $topic,
        ]);

        switch ($topic) {
            case 'app/uninstalled':
                $triggeredAt = $request->header('X-Shopify-Triggered-At');

                return $this->handleAppUninstalled($canonicalShop, $data, $triggeredAt);

            case 'customers/data_request':
                return $this->handleCustomerDataRequest($data, $canonicalShop);

            case 'customers/redact':
                return $this->handleCustomerRedact($data, $canonicalShop);

            case 'shop/redact':
                return $this->handleShopRedact($data, $canonicalShop);

            case 'app_subscriptions/update':
                return $this->handleAppSubscriptionsUpdate($canonicalShop, $data);

            default:
                Log::info("Unhandled Shopify webhook topic: {$topic}", ['shop' => $canonicalShop]);

                return response()->json(['status' => 'success', 'message' => 'Webhook received']);
        }
    }

    /**
     * Mark a store as uninstalled idempotently (preserves data for potential re-install).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleAppUninstalled(?string $shop, array $data = [], ?string $triggeredAt = null): JsonResponse
    {
        $rawDomain = $shop ?: ($data['domain'] ?? $data['myshopify_domain'] ?? null);
        $canonicalShop = $this->shopifyService->normalizeShopDomain($rawDomain);

        if (! $canonicalShop) {
            return response()->json(['status' => 'error', 'message' => 'Missing or invalid shop domain'], 400);
        }

        $store = ShopifyStore::where('shop', $canonicalShop)->first();

        if (! $store) {
            Log::info("Uninstall webhook received for unknown store: {$canonicalShop}");

            return response()->json(['status' => 'success', 'message' => 'Store not found']);
        }

        // Idempotency: If the store is already uninstalled, do not overwrite timestamp or re-process
        if (! $store->is_active && $store->uninstalled_at !== null) {
            Log::info("Uninstall webhook ignored: store already uninstalled: {$canonicalShop}");

            return response()->json(['status' => 'success', 'message' => 'Store already uninstalled']);
        }

        // Stale event protection: If the store was reinstalled AFTER this webhook was triggered, ignore it
        if ($triggeredAt) {
            try {
                $webhookTime = Carbon::parse($triggeredAt);
                if ($store->installed_at && $webhookTime->lt($store->installed_at)) {
                    Log::warning('Stale uninstall webhook ignored: store reinstalled after webhook event', [
                        'shop' => $canonicalShop,
                        'webhook_triggered_at' => $webhookTime->toIso8601String(),
                        'installed_at' => $store->installed_at->toIso8601String(),
                    ]);

                    return response()->json(['status' => 'success', 'message' => 'Stale webhook ignored']);
                }
            } catch (\Throwable) {
                // If timestamp cannot be parsed, continue safely
            }
        }

        $store->markUninstalled();
        Log::info("Store marked as uninstalled: {$canonicalShop}");

        return response()->json(['status' => 'success', 'message' => 'Store marked uninstalled']);
    }

    /**
     * Process app_subscriptions/update webhook from Shopify.
     * Idempotently synchronizes subscription cancellations, expirations, and activations.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleAppSubscriptionsUpdate(?string $shop, array $data = []): JsonResponse
    {
        $canonicalShop = $shop ?: $this->shopifyService->normalizeShopDomain($data['shop'] ?? null);

        if (! $canonicalShop) {
            return response()->json(['status' => 'error', 'message' => 'Missing or invalid shop domain'], 400);
        }

        $store = ShopifyStore::where('shop', $canonicalShop)->first();

        if (! $store) {
            Log::info("Billing webhook received for unknown store: {$canonicalShop}");

            return response()->json(['status' => 'success', 'message' => 'Store not found']);
        }

        $appSub = $data['app_subscription'] ?? $data;
        $chargeId = $appSub['admin_graphql_api_id'] ?? $appSub['id'] ?? null;
        $rawStatus = strtoupper((string) ($appSub['status'] ?? ''));

        Log::info('Processing app_subscriptions/update webhook', [
            'shop' => $canonicalShop,
            'charge_id' => $chargeId,
            'shopify_status' => $rawStatus,
        ]);

        $subscriptionService = app(SubscriptionService::class);

        if (in_array($rawStatus, ['CANCELLED', 'EXPIRED', 'DECLINED', 'FROZEN'], true)) {
            // Find existing active Pro subscription matching charge ID or store
            $query = ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE);

            if (! empty($chargeId)) {
                $query->where('shopify_charge_id', (string) $chargeId);
            }

            $affected = $query->update([
                'status' => ShopSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            // Clear store plan_name to unassigned state and clear verification caches
            $store->update(['plan_name' => null]);
            app(ShopifyPricingService::class)->invalidateStoreSubscriptionCache($store);
            Cache::forget("shopify:store:{$store->id}:subscription:verified");

            Log::info('Handled subscription cancellation/expiration via webhook', [
                'shop' => $canonicalShop,
                'charge_id' => $chargeId,
                'affected_subscriptions' => $affected,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Subscription cancelled and store reverted to unassigned free tier']);
        }

        if ($rawStatus === 'ACTIVE') {
            $subName = (string) ($appSub['name'] ?? '');

            // Resolve plan dynamically by exact slug or subscription name
            $matchedPlan = null;
            if (! empty($subName)) {
                $slugCandidate = Str::slug($subName);
                $matchedPlan = Plan::where('slug', $slugCandidate)->where('is_active', true)->first();

                if (! $matchedPlan) {
                    foreach (['starter', 'pro', 'growth', 'free'] as $kw) {
                        if (stripos($subName, $kw) !== false) {
                            $matchedPlan = Plan::where('slug', $kw)->where('is_active', true)->first();
                            if ($matchedPlan) {
                                break;
                            }
                        }
                    }
                }

                if (! $matchedPlan) {
                    $matchedPlan = Plan::where('name', 'like', "%{$subName}%")->where('is_active', true)->first();
                }
            }

            // Fallback: default to Pro or Free
            $matchedPlan ??= Plan::where('slug', 'pro')->where('is_active', true)->first()
                ?? Plan::where('price', '>', 0)->where('is_active', true)->first()
                ?? Plan::where('slug', 'free')->first();

            if ($matchedPlan) {
                // Idempotently ensure subscription is recorded
                $alreadyActive = ShopSubscription::where('shop_id', $store->id)
                    ->where(function ($q) use ($chargeId): void {
                        if ($chargeId) {
                            $q->where('shopify_charge_id', (string) $chargeId);
                        }
                    })
                    ->where('status', ShopSubscription::STATUS_ACTIVE)
                    ->where('plan_id', $matchedPlan->id)
                    ->exists();

                if (! $alreadyActive) {
                    if ($matchedPlan->isFree()) {
                        $subscriptionService->assignFreeSubscription($store);
                        if ($chargeId) {
                            $store->subscriptions()->where('status', ShopSubscription::STATUS_ACTIVE)->latest('id')->first()?->update([
                                'shopify_charge_id' => (string) $chargeId,
                            ]);
                        }
                    } else {
                        $subscriptionService->activateSubscription(
                            $store,
                            $matchedPlan,
                            $chargeId ? (string) $chargeId : null,
                            [
                                'activated_by' => 'webhook',
                                'shopify_status' => $rawStatus,
                                'plan_handle' => $matchedPlan->slug,
                            ]
                        );
                    }

                    Log::info('Activated subscription via webhook with dynamic plan mapping', [
                        'shop' => $canonicalShop,
                        'charge_id' => $chargeId,
                        'plan_id' => $matchedPlan->id,
                        'plan_slug' => $matchedPlan->slug,
                        'plan_name' => $matchedPlan->name,
                    ]);
                }

                // If Partner API is configured, verify and synchronize full snapshot
                $pricingService = app(ShopifyPricingService::class);
                if ($pricingService->isPartnerApiConfigured()) {
                    $pricingService->verifyAndSyncActiveSubscription($store, $matchedPlan->slug);
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Subscription verified active']);
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook received with status: '.$rawStatus]);
    }

    /**
     * Process customers/data_request mandatory GDPR webhook.
     * Retrieves all customer-related records stored by SocialNest and exports them securely.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerDataRequest(array $payload, ?string $canonicalShop): JsonResponse
    {
        $shopDomain = $canonicalShop ?: $this->shopifyService->normalizeShopDomain($payload['shop_domain'] ?? null);
        $customerId = isset($payload['customer']['id']) ? (string) $payload['customer']['id'] : null;

        if (empty($shopDomain)) {
            Log::warning('Shopify customer data request missing shop domain');

            return response()->json(['status' => 'error', 'message' => 'Missing shop domain'], 400);
        }

        if (empty($customerId)) {
            Log::warning('Shopify customer data request missing customer id', ['shop' => $shopDomain]);

            return response()->json(['status' => 'error', 'message' => 'Missing customer id'], 400);
        }

        $store = ShopifyStore::where('shop', $shopDomain)->first();

        if (! $store) {
            Log::info('Shopify customer data request: store not found in database', ['shop' => $shopDomain]);

            return response()->json([
                'status' => 'success',
                'message' => 'Store not found, zero customer records stored',
                'data' => [
                    'shop' => $shopDomain,
                    'customer_id' => $customerId,
                    'wishlist_items' => [],
                    'record_count' => 0,
                ],
            ]);
        }

        // Retrieve customer wishlist records strictly scoped to this store
        $wishlistItems = WishlistItem::forShop($store->id)
            ->forCustomer($customerId)
            ->get();

        $exportData = [
            'shop' => $store->shop,
            'customer_id' => $customerId,
            'requested_at' => now()->toIso8601String(),
            'wishlist_items' => $wishlistItems->map(fn (WishlistItem $item) => $item->toStorefrontArray())->values()->all(),
            'record_count' => $wishlistItems->count(),
        ];

        // Store export file locally for store owner retrieval
        try {
            Storage::disk('local')->put(
                "gdpr/exports/{$store->id}/customer_{$customerId}.json",
                json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to write GDPR customer export file', [
                'shop' => $store->shop,
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Shopify customer data request fulfilled', [
            'shop' => $store->shop,
            'customer_id' => $customerId,
            'record_count' => $wishlistItems->count(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer data retrieved and export created',
            'data' => $exportData,
        ]);
    }

    /**
     * Merchant-facing secure download endpoint for GDPR customer data exports.
     * Requires shopify.auth middleware to verify that only the authenticated store owner can access it.
     */
    public function downloadCustomerExport(Request $request, string $customerId): Response|JsonResponse
    {
        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store || ! $store->isInstalled()) {
            return response()->json(['error' => 'Unauthorized store context'], 401);
        }

        $cleanCustomerId = trim($customerId);

        // Prevent path traversal and arbitrary file access with strict identifier validation
        if (! preg_match('/^[a-zA-Z0-9_\-]+$/', $cleanCustomerId)) {
            return response()->json(['error' => 'Invalid customer ID format'], 400);
        }

        $cleanFilename = basename("customer_{$cleanCustomerId}.json");
        $relativePath = "gdpr/exports/{$store->id}/{$cleanFilename}";

        if (! Storage::disk('local')->exists($relativePath)) {
            return response()->json(['error' => 'Customer export not found'], 404);
        }

        $content = Storage::disk('local')->get($relativePath);

        return response($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"customer_{$cleanCustomerId}_export.json\"",
        ]);
    }

    /**
     * Process customers/redact mandatory GDPR webhook.
     * Deletes only customer-specific records for the target shop without touching guest records.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerRedact(array $payload, ?string $canonicalShop): JsonResponse
    {
        $shopDomain = $canonicalShop ?: $this->shopifyService->normalizeShopDomain($payload['shop_domain'] ?? null);
        $customerId = isset($payload['customer']['id']) ? (string) $payload['customer']['id'] : null;

        if (empty($shopDomain)) {
            Log::warning('Shopify customer redact request missing shop domain');

            return response()->json(['status' => 'error', 'message' => 'Missing shop domain'], 400);
        }

        if (empty($customerId)) {
            Log::warning('Shopify customer redact request missing customer id', ['shop' => $shopDomain]);

            return response()->json(['status' => 'error', 'message' => 'Missing customer id'], 400);
        }

        $store = ShopifyStore::where('shop', $shopDomain)->first();

        if (! $store) {
            Log::info('Shopify customer redact: store not found, nothing to delete', ['shop' => $shopDomain]);

            return response()->json(['status' => 'success', 'message' => 'Store not found, no records to redact']);
        }

        try {
            DB::transaction(function () use ($store, $customerId): void {
                // Delete only customer records for this shop; guest records (where customer_id IS NULL) remain untouched
                WishlistItem::forShop($store->id)
                    ->forCustomer($customerId)
                    ->delete();
            });

            // Clean up any existing customer data export file
            if (Storage::disk('local')->exists("gdpr/exports/{$store->id}/customer_{$customerId}.json")) {
                Storage::disk('local')->delete("gdpr/exports/{$store->id}/customer_{$customerId}.json");
            }

            Log::info('Shopify customer redact completed successfully', [
                'shop' => $store->shop,
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer data redacted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Shopify customer redact database failure', [
                'shop' => $store->shop,
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Database error during customer redaction'], 500);
        }
    }

    /**
     * Process shop/redact mandatory GDPR webhook.
     * Fully erases all shop-owned database records and uploaded files within Shopify's required timeframe.
     * Guaranteed failure-safe: Database transaction executes first and cannot be blocked by filesystem cleanup errors.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleShopRedact(array $payload, ?string $canonicalShop): JsonResponse
    {
        $shopDomain = $canonicalShop ?: $this->shopifyService->normalizeShopDomain($payload['shop_domain'] ?? null);

        if (empty($shopDomain)) {
            Log::warning('Shopify shop redact request missing shop domain');

            return response()->json(['status' => 'error', 'message' => 'Missing shop domain'], 400);
        }

        $store = ShopifyStore::where('shop', $shopDomain)->first();

        // 1. If store is already deleted from DB, check for any pending filesystem cleanup retry in database
        if (! $store) {
            $pendingRecord = PendingShopRedaction::where('shop_domain', $shopDomain)->first();

            if ($pendingRecord) {
                Log::info('Shopify shop redact retry: attempting pending filesystem cleanup', [
                    'shop' => $shopDomain,
                    'store_id' => $pendingRecord->store_id,
                ]);

                $retrySuccess = $this->shopRedactionService->purgeShopFilesystem((int) $pendingRecord->store_id);

                if ($retrySuccess) {
                    $pendingRecord->delete();
                    Log::info('Shopify shop redact retry: filesystem cleanup succeeded', [
                        'shop' => $shopDomain,
                        'store_id' => $pendingRecord->store_id,
                    ]);

                    return response()->json(['status' => 'success', 'message' => 'Shop data and pending files erased successfully']);
                }

                $pendingRecord->increment('attempts', 1, [
                    'last_attempt_at' => now(),
                    'last_error' => 'Filesystem purge did not completely remove all directories',
                ]);

                Log::warning('Shopify shop redact retry: filesystem cleanup failed again', [
                    'shop' => $shopDomain,
                    'store_id' => $pendingRecord->store_id,
                    'attempts' => $pendingRecord->attempts,
                ]);

                return response()->json(['status' => 'partial_failure', 'message' => 'Filesystem cleanup retry pending'], 500);
            }

            Log::info('Shopify shop redact: store already erased or not found', ['shop' => $shopDomain]);

            return response()->json(['status' => 'success', 'message' => 'Store already erased or does not exist']);
        }

        // 2. Capture store ID and domain before deletion
        $storeId = (int) $store->id;
        $pendingRecord = null;

        // 3. Within the database transaction: record pending redaction and delete all shop records
        try {
            DB::transaction(function () use ($store, $shopDomain, $storeId, &$pendingRecord): void {
                $pendingRecord = PendingShopRedaction::updateOrCreate(
                    ['shop_domain' => $shopDomain],
                    [
                        'store_id' => $storeId,
                        'last_attempt_at' => now(),
                    ]
                );

                $store->socialLinks()->delete();
                $store->storefrontSettings()->delete();
                $store->clicks()->delete();
                $store->wishlistItems()->delete();
                $store->subscriptions()->delete();
                $store->delete();
            });
        } catch (\Throwable $dbEx) {
            Log::error('Shopify shop redact database transaction failed', [
                'shop' => $shopDomain,
                'store_id' => $storeId,
                'error' => $dbEx->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Database error during shop redaction'], 500);
        }

        // 4. After database commit succeeds, perform filesystem asset cleanup
        $filesCleaned = $this->shopRedactionService->purgeShopFilesystem($storeId);

        if ($filesCleaned) {
            $pendingRecord?->delete();

            Log::info('Shopify shop redact completed successfully', [
                'shop' => $shopDomain,
                'store_id' => $storeId,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Shop data erased successfully']);
        }

        // 5. If filesystem cleanup failed, update attempts and error on pending record and return 500 to trigger Shopify webhook retry
        $pendingRecord?->increment('attempts', 1, [
            'last_attempt_at' => now(),
            'last_error' => 'Filesystem purge did not completely remove all directories',
        ]);

        Log::warning('Shopify shop redact: database erased but filesystem cleanup pending retry', [
            'shop' => $shopDomain,
            'store_id' => $storeId,
        ]);

        return response()->json([
            'status' => 'partial_failure',
            'message' => 'Database erased; filesystem cleanup pending retry',
        ], 500);
    }

    /**
     * Safely purge all filesystem assets associated with a specific store ID.
     */
    protected function purgeShopFilesystem(int $storeId): bool
    {
        return $this->shopRedactionService->purgeShopFilesystem($storeId);
    }
}
