<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ShopifyPricingService
{
    /**
     * Cache TTL for verified subscription state in seconds.
     */
    public const SUBSCRIPTION_CACHE_TTL_SECONDS = 300;

    public function __construct(
        protected ShopifyService $shopifyService,
        protected ?SubscriptionService $subscriptionService = null
    ) {
        $this->subscriptionService ??= app(SubscriptionService::class);
    }

    /**
     * Determine if all required Partner API credentials are configured.
     */
    public function isPartnerApiConfigured(): bool
    {
        $orgId = config('shopify.partner_org_id');
        $token = config('shopify.partner_api_access_token');
        $appGid = config('shopify.app_gid');

        return ! empty($orgId) && ! empty($token) && ! empty($appGid);
    }

    /**
     * Get list of missing Partner API configuration keys.
     *
     * @return list<string>
     */
    public function getMissingConfigurationDetails(): array
    {
        $missing = [];
        if (empty(config('shopify.partner_org_id'))) {
            $missing[] = 'SHOPIFY_PARTNER_ORG_ID';
        }
        if (empty(config('shopify.partner_api_access_token'))) {
            $missing[] = 'SHOPIFY_PARTNER_API_ACCESS_TOKEN';
        }
        if (empty(config('shopify.app_gid'))) {
            $missing[] = 'SHOPIFY_APP_GID';
        }

        return $missing;
    }

    /**
     * Resolve the Shopify Partner API endpoint URL.
     */
    public function getPartnerApiEndpoint(): string
    {
        $orgId = (string) config('shopify.partner_org_id');
        $apiVersion = (string) config('shopify.partner_api_version', '2026-10');

        return "https://partners.shopify.com/{$orgId}/api/{$apiVersion}/graphql.json";
    }

    /**
     * Resolve the Shopify Shop GID for a store via Admin GraphQL query { shop { id } }.
     */
    public function resolveShopGid(ShopifyStore $store): ?string
    {
        if (! empty($store->shopify_shop_gid) && str_starts_with($store->shopify_shop_gid, 'gid://shopify/Shop/')) {
            return $store->shopify_shop_gid;
        }

        try {
            $query = <<<'GRAPHQL'
                query GetShopGid {
                    shop {
                        id
                    }
                }
                GRAPHQL;

            $response = $this->shopifyService->executeGraphQL($store, $query);
            $shopGid = $response['data']['shop']['id'] ?? null;

            if ($shopGid && is_string($shopGid)) {
                $store->update(['shopify_shop_gid' => $shopGid]);

                Log::info('Resolved and persisted Shopify Shop GID', [
                    'shop' => $store->shop,
                    'shop_gid' => $shopGid,
                ]);

                return $shopGid;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve Shopify Shop GID via Admin GraphQL', [
                'shop' => $store->shop,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Query the active managed pricing subscription from Shopify Partner API.
     *
     * @return array{
     *     success: bool,
     *     error: ?string,
     *     message: ?string,
     *     is_rate_limited?: bool,
     *     subscription_data: ?array<string, mixed>
     * }
     */
    public function queryPartnerActiveSubscription(ShopifyStore $store): array
    {
        if (! $this->isPartnerApiConfigured()) {
            $missing = $this->getMissingConfigurationDetails();
            Log::debug('Shopify Partner API credentials not configured in environment', [
                'shop' => $store->shop,
                'missing_keys' => $missing,
            ]);

            return [
                'success' => false,
                'error' => 'MISSING_PARTNER_CREDENTIALS',
                'message' => 'Partner API credentials are not configured: '.implode(', ', $missing),
                'subscription_data' => null,
            ];
        }

        $shopGid = $this->resolveShopGid($store);
        if (! $shopGid) {
            return [
                'success' => false,
                'error' => 'UNRESOLVED_SHOP_GID',
                'message' => "Could not resolve Shopify Shop GID for {$store->shop}",
                'subscription_data' => null,
            ];
        }

        $appGid = (string) config('shopify.app_gid');
        $endpoint = $this->getPartnerApiEndpoint();
        $token = (string) config('shopify.partner_api_access_token');

        $query = <<<'GRAPHQL'
            query ActiveSubscription($appId: ID!, $shopId: ID!) {
                activeSubscription(appId: $appId, shopId: $shopId) {
                    shop {
                        id
                        myshopifyDomain
                    }
                    billingPeriod
                    cancelAtEndOfCycle
                    trialEndsAt
                    currentBillingCycle {
                        startTime
                        endTime
                    }
                    items {
                        handle
                        description
                        price {
                            __typename
                            ... on FlatRatePrice {
                                amount {
                                    amount
                                    currencyCode
                                }
                            }
                        }
                    }
                    pendingUpdate {
                        billingPeriod
                    }
                }
            }
            GRAPHQL;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout(12)
                ->post($endpoint, [
                    'query' => $query,
                    'variables' => [
                        'appId' => $appGid,
                        'shopId' => $shopGid,
                    ],
                ]);

            if ($response->status() === 429) {
                Log::warning('Shopify Partner API rate limited', [
                    'shop' => $store->shop,
                    'endpoint' => $endpoint,
                ]);

                return [
                    'success' => false,
                    'error' => 'RATE_LIMITED',
                    'message' => 'Shopify Partner API rate limit encountered. Please retry shortly.',
                    'is_rate_limited' => true,
                    'subscription_data' => null,
                ];
            }

            if (! $response->successful()) {
                Log::warning('Shopify Partner API request returned non-success HTTP status', [
                    'shop' => $store->shop,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'error' => 'HTTP_ERROR',
                    'message' => "Partner API returned HTTP status {$response->status()}",
                    'subscription_data' => null,
                ];
            }

            $payload = $response->json();

            if (! empty($payload['errors'])) {
                Log::warning('Shopify Partner API GraphQL errors', [
                    'shop' => $store->shop,
                    'errors' => $payload['errors'],
                ]);

                return [
                    'success' => false,
                    'error' => 'GRAPHQL_ERROR',
                    'message' => $payload['errors'][0]['message'] ?? 'GraphQL error in Partner API response',
                    'subscription_data' => null,
                ];
            }

            $activeSub = $payload['data']['activeSubscription'] ?? null;

            return [
                'success' => true,
                'error' => null,
                'message' => null,
                'subscription_data' => $activeSub,
            ];
        } catch (ConnectionException $ce) {
            Log::error('Shopify Partner API timeout / connection exception', [
                'shop' => $store->shop,
                'error' => $ce->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'TIMEOUT',
                'message' => 'Partner API connection timed out. Preserving existing verified state.',
                'subscription_data' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Shopify Partner API request exception', [
                'shop' => $store->shop,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'API_EXCEPTION',
                'message' => 'Partner API request failed: '.$e->getMessage(),
                'subscription_data' => null,
            ];
        }
    }

    /**
     * Verify and synchronize the active subscription for a store via Partner API.
     *
     * @return array{
     *     success: bool,
     *     error: ?string,
     *     message: string,
     *     subscription: ?ShopSubscription
     * }
     */
    public function verifyAndSyncActiveSubscription(
        ShopifyStore $store,
        ?string $expectedPlanHandle = null
    ): array {
        $expectedHandle = $expectedPlanHandle ? strtolower(trim($expectedPlanHandle)) : null;

        // If Partner API is explicitly configured, attempt Partner API synchronization
        if ($this->isPartnerApiConfigured()) {
            $result = $this->queryPartnerActiveSubscription($store);
            if ($result['success'] && ! empty($result['subscription_data'])) {
                return $this->syncPartnerSubscriptionToDatabase($store, $result['subscription_data'], $expectedHandle);
            }
        }

        // Primary / standard flow: Query store Shopify Admin GraphQL API
        if (! empty($store->access_token)) {
            Log::debug('[verifyAndSyncActiveSubscription] Querying Shopify Admin GraphQL API for activeSubscriptions', [
                'shop' => $store->shop,
            ]);

            $detailedResult = $this->queryAdminActiveSubscriptionsDetailed($store);

            if ($detailedResult['status'] === 'ACTIVE_FOUND' && ! empty($detailedResult['subscriptions'])) {
                $activeAdminSub = $detailedResult['subscriptions'][0];
                if (($activeAdminSub['status'] ?? '') === 'ACTIVE') {
                    return $this->syncAdminSubscriptionToDatabase($store, $activeAdminSub, $expectedHandle);
                }
            }

            if ($detailedResult['status'] === 'QUERY_FAILED') {
                Log::warning('[verifyAndSyncActiveSubscription] Shopify Admin GraphQL API query failed; preserving existing local subscription state', [
                    'shop' => $store->shop,
                    'error' => $detailedResult['error'],
                ]);

                return [
                    'success' => false,
                    'status' => 'QUERY_FAILED',
                    'error' => 'SHOPIFY_API_FAILED',
                    'message' => 'Failed to retrieve active subscriptions from Shopify API: '.($detailedResult['error'] ?? 'Unknown error'),
                    'subscription' => null,
                ];
            }

            // Definitively verified empty active subscriptions on Shopify
            return [
                'success' => false,
                'status' => 'NONE_FOUND',
                'error' => 'NO_ACTIVE_SUBSCRIPTION',
                'message' => 'No active subscription found on Shopify for this store.',
                'subscription' => null,
            ];
        }
    }

    /**
     * Internal helper to synchronize a Partner API subscription dataset to the database.
     *
     * @param  array<string, mixed>  $subData
     * @return array{success: bool, error: ?string, message: string, subscription: ?ShopSubscription}
     */
    protected function syncPartnerSubscriptionToDatabase(ShopifyStore $store, array $subData, ?string $expectedHandle = null): array
    {

        // If Partner API returned null, shop does not have an active managed pricing subscription
        if (empty($subData)) {
            Log::info('Shopify Partner API confirmed no active subscription for shop', [
                'shop' => $store->shop,
            ]);

            return [
                'success' => false,
                'error' => 'NO_ACTIVE_SUBSCRIPTION',
                'message' => 'No active managed pricing subscription found on Shopify for this store.',
                'subscription' => null,
            ];
        }

        // Verify shop attribution
        $subShopDomain = $subData['shop']['myshopifyDomain'] ?? null;
        if (! empty($subShopDomain)) {
            $canonicalSubShop = $this->shopifyService->normalizeShopDomain($subShopDomain);
            if ($canonicalSubShop !== $store->shop) {
                Log::warning('Partner API subscription shop domain mismatch', [
                    'expected' => $store->shop,
                    'returned' => $canonicalSubShop,
                ]);

                return [
                    'success' => false,
                    'error' => 'SHOP_MISMATCH',
                    'message' => 'Subscription belongs to a different shop domain.',
                    'subscription' => null,
                ];
            }
        }

        // Verify shop GID attribution
        $subShopGid = $subData['shop']['id'] ?? null;
        $expectedShopGid = $store->shopify_shop_gid ?? $this->resolveShopGid($store);
        if (! empty($subShopGid) && ! empty($expectedShopGid) && $subShopGid !== $expectedShopGid) {
            Log::warning('Partner API subscription shop GID mismatch', [
                'expected' => $expectedShopGid,
                'returned' => $subShopGid,
                'shop' => $store->shop,
            ]);

            return [
                'success' => false,
                'error' => 'SHOP_GID_MISMATCH',
                'message' => 'Subscription shop GID does not match authenticated store GID.',
                'subscription' => null,
            ];
        }

        // Verify application GID attribution
        $expectedAppGid = config('shopify.app_gid');
        $subAppGid = $subData['app']['id'] ?? null;
        if (! empty($subAppGid) && ! empty($expectedAppGid) && $subAppGid !== $expectedAppGid) {
            Log::warning('Partner API subscription app GID mismatch', [
                'expected' => $expectedAppGid,
                'returned' => $subAppGid,
            ]);

            return [
                'success' => false,
                'error' => 'APP_GID_MISMATCH',
                'message' => 'Subscription app GID does not match configured application.',
                'subscription' => null,
            ];
        }

        // Verify items and plan handle
        $items = $subData['items'] ?? [];
        $matchedItem = null;
        foreach ($items as $item) {
            $itemHandle = strtolower(trim((string) ($item['handle'] ?? '')));
            if ($itemHandle === $expectedHandle || (empty($expectedHandle) && ! empty($itemHandle))) {
                $matchedItem = $item;
                break;
            }
        }

        if (! $matchedItem && ! empty($expectedHandle)) {
            Log::warning('Partner API subscription plan handle mismatch', [
                'shop' => $store->shop,
                'expected_handle' => $expectedHandle,
                'items' => $items,
            ]);

            return [
                'success' => false,
                'error' => 'PLAN_HANDLE_MISMATCH',
                'message' => "Active subscription item handle does not match expected plan [{$expectedHandle}].",
                'subscription' => null,
            ];
        }

        // Synchronize verified subscription to database inside a transaction
        return DB::transaction(function () use ($store, $subData, $matchedItem, $expectedHandle): array {
            $now = now();
            $cycleStart = isset($subData['currentBillingCycle']['startTime'])
                ? now()->parse($subData['currentBillingCycle']['startTime'])
                : $now;
            $cycleEnd = isset($subData['currentBillingCycle']['endTime'])
                ? now()->parse($subData['currentBillingCycle']['endTime'])
                : null;
            $cancelAtEnd = (bool) ($subData['cancelAtEndOfCycle'] ?? false);
            $billingPeriod = (string) ($subData['billingPeriod'] ?? 'PERMANENT');

            $targetHandle = strtolower(trim((string) ($matchedItem['handle'] ?? $expectedHandle ?? 'free')));

            // 1. Match local plan dynamically by exact handle/slug
            $plan = Plan::where('slug', $targetHandle)->where('is_active', true)->first();

            // 2. Match by slug derived from subscription name
            if (! $plan && ! empty($subData['name'])) {
                $subName = (string) $subData['name'];
                $slugCandidate = Str::slug($subName);
                $plan = Plan::where('slug', $slugCandidate)->where('is_active', true)->first();

                if (! $plan) {
                    foreach (['starter', 'pro', 'growth', 'free'] as $kw) {
                        if (stripos($subName, $kw) !== false) {
                            $plan = Plan::where('slug', $kw)->where('is_active', true)->first();
                            if ($plan) {
                                break;
                            }
                        }
                    }
                }

                if (! $plan) {
                    $plan = Plan::where('name', 'like', "%{$subName}%")->where('is_active', true)->first();
                }
            }

            // 3. Fallback: match by handle keyword
            if (! $plan) {
                $plan = Plan::where('name', 'like', "%{$targetHandle}%")->where('is_active', true)->first();
            }

            // 4. Fallback: default to Free plan
            if (! $plan) {
                $plan = Plan::where('slug', 'free')->first()
                    ?? Plan::where('price', '<=', 0)->first()
                    ?? Plan::first();
            }

            // Sanitize response snapshot before storing (strip tokens/secrets)
            $sanitizedSnapshot = [
                'billingPeriod' => $subData['billingPeriod'] ?? null,
                'cancelAtEndOfCycle' => $cancelAtEnd,
                'trialEndsAt' => $subData['trialEndsAt'] ?? null,
                'currentBillingCycle' => $subData['currentBillingCycle'] ?? null,
                'items' => $subData['items'] ?? [],
                'synced_at' => $now->toIso8601String(),
            ];

            // Expire any existing active non-matching subscriptions
            ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update(['status' => ShopSubscription::STATUS_EXPIRED]);

            // Create or update authoritative verified active subscription
            $subscription = ShopSubscription::create([
                'shop_id' => $store->id,
                'plan_id' => $plan?->id ?? 1,
                'shopify_app_gid' => config('shopify.app_gid'),
                'plan_handle' => $targetHandle,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'cancel_at_period_end' => $cancelAtEnd,
                'activated_at' => $now,
                'last_verified_at' => $now,
                'billing_interval' => $billingPeriod,
                'current_period_start' => $cycleStart,
                'current_period_end' => $cycleEnd,
                'metadata' => [
                    'source' => 'shopify_app_pricing_partner_api',
                    'item_handle' => $matchedItem['handle'] ?? $targetHandle,
                    'plan_name' => $plan?->name ?? ($subData['name'] ?? null),
                ],
                'response_snapshot' => $sanitizedSnapshot,
            ]);

            // Update store plan_name dynamically matching plan or subscription name
            $store->update(['plan_name' => $plan?->name ?? ($subData['name'] ?? 'Socialvire Free')]);

            $this->invalidateStoreSubscriptionCache($store);

            Log::info('Successfully verified and synchronized Shopify App Pricing subscription', [
                'shop' => $store->shop,
                'subscription_id' => $subscription->id,
                'plan_handle' => $subscription->plan_handle,
            ]);

            return [
                'success' => true,
                'error' => null,
                'message' => 'Socialvire Free plan verified and synchronized successfully.',
                'subscription' => $subscription,
            ];
        });
    }

    /**
     * Synchronize an active subscription returned by Shopify Admin GraphQL into the database.
     *
     * @param  array{id: string, name: string, status: string, currentPeriodEnd?: ?string}  $adminSub
     * @return array{success: bool, error: ?string, message: string, subscription: ?ShopSubscription}
     */
    public function syncAdminSubscriptionToDatabase(ShopifyStore $store, array $adminSub, ?string $expectedHandle = null): array
    {
        Log::info('[verifyAndSyncActiveSubscription] Syncing subscription from Admin GraphQL', [
            'shop' => $store->shop,
            'adminSub' => $adminSub,
        ]);

        return DB::transaction(function () use ($store, $adminSub, $expectedHandle): array {
            $now = now();
            $subGid = (string) ($adminSub['id'] ?? '');
            $subName = (string) ($adminSub['name'] ?? 'Socialvire Starter');
            $periodEnd = isset($adminSub['currentPeriodEnd']) ? now()->parse($adminSub['currentPeriodEnd']) : null;

            // Match local plan dynamically
            $matchedPlan = null;
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

            if (! $matchedPlan && ! empty($expectedHandle)) {
                $matchedPlan = Plan::where('slug', $expectedHandle)->where('is_active', true)->first();
            }

            if (! $matchedPlan) {
                $matchedPlan = Plan::where('name', 'like', "%{$subName}%")->where('is_active', true)->first();
            }

            if (! $matchedPlan) {
                $matchedPlan = Plan::where('slug', 'starter')->first()
                    ?? Plan::where('slug', 'free')->first()
                    ?? Plan::where('price', '<=', 0)->first()
                    ?? Plan::first();
            }

            // Check for existing active subscription matching this charge ID or plan
            $existing = null;
            if (! empty($subGid)) {
                $existing = ShopSubscription::where('shop_id', $store->id)
                    ->where('shopify_charge_id', $subGid)
                    ->first();
            }

            if (! $existing && $matchedPlan) {
                $existing = ShopSubscription::where('shop_id', $store->id)
                    ->where('plan_id', $matchedPlan->id)
                    ->where('status', ShopSubscription::STATUS_ACTIVE)
                    ->first();
            }

            // Expire any other active subscriptions that don't match this subscription
            $otherSubsQuery = ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE);
            if (! empty($subGid)) {
                $otherSubsQuery->where('shopify_charge_id', '!=', $subGid);
            }
            if ($existing) {
                $otherSubsQuery->where('id', '!=', $existing->id);
            }
            $otherSubsQuery->update(['status' => ShopSubscription::STATUS_EXPIRED]);

            if ($existing) {
                $existing->update([
                    'plan_id' => $matchedPlan?->id ?? $existing->plan_id,
                    'status' => ShopSubscription::STATUS_ACTIVE,
                    'last_verified_at' => $now,
                    'current_period_end' => $periodEnd,
                    'plan_handle' => $matchedPlan?->slug ?? $expectedHandle ?? $existing->plan_handle,
                    'shopify_charge_id' => $subGid ?: $existing->shopify_charge_id,
                ]);
                $subscription = $existing;
            } else {
                $subscription = ShopSubscription::create([
                    'shop_id' => $store->id,
                    'plan_id' => $matchedPlan?->id ?? 1,
                    'shopify_charge_id' => $subGid,
                    'shopify_app_gid' => config('shopify.app_gid'),
                    'plan_handle' => $matchedPlan?->slug ?? $expectedHandle,
                    'status' => ShopSubscription::STATUS_ACTIVE,
                    'cancel_at_period_end' => false,
                    'activated_at' => $now,
                    'last_verified_at' => $now,
                    'billing_interval' => 'PERMANENT',
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                    'metadata' => [
                        'source' => 'shopify_admin_graphql',
                        'sub_id' => $subGid,
                        'sub_name' => $subName,
                    ],
                ]);
            }

            $store->update(['plan_name' => $matchedPlan?->name ?? $subName]);
            $this->invalidateStoreSubscriptionCache($store);

            return [
                'success' => true,
                'error' => null,
                'message' => 'Subscription verified and synchronized via Shopify Admin GraphQL.',
                'subscription' => $subscription,
            ];
        });
    }

    /**
     * Cancel an active subscription and synchronize the status locally.
     */
    public function cancelSubscription(ShopifyStore $store, ?string $subscriptionId = null, bool $prorate = false): ShopSubscription
    {
        return DB::transaction(function () use ($store): ShopSubscription {
            $now = now();

            $activeSubscription = ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            if (! $activeSubscription) {
                throw new RuntimeException('No active subscription found to cancel.');
            }

            $activeSubscription->update([
                'status' => ShopSubscription::STATUS_CANCELLED,
                'cancelled_at' => $now,
                'cancel_at_period_end' => false,
            ]);

            // Unlink store from active plan name without creating a ghost free subscription
            $store->update(['plan_name' => null]);

            $this->invalidateStoreSubscriptionCache($store);

            Log::info('Cancelled subscription locally and reverted store access to free tier', [
                'shop' => $store->shop,
                'subscription_id' => $activeSubscription->id,
            ]);

            return $activeSubscription->fresh('plan');
        });
    }

    /**
     * Execute a GraphQL query or mutation against Shopify Partner API.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function executePartnerApiQuery(string $query, array $variables = []): array
    {
        $endpoint = $this->getPartnerApiEndpoint();
        $token = (string) config('shopify.partner_api_access_token');

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(12)
            ->post($endpoint, [
                'query' => $query,
                'variables' => $variables,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Alias for executePartnerApiQuery.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function sendPartnerApiRequest(string $query, array $variables = []): array
    {
        return $this->executePartnerApiQuery($query, $variables);
    }

    /**
     * Query active subscriptions for the store directly from Shopify Admin GraphQL API with explicit status tracking.
     *
     * @return array{
     *     success: bool,
     *     status: 'ACTIVE_FOUND'|'NONE_FOUND'|'QUERY_FAILED',
     *     error: ?string,
     *     subscriptions: array<int, array{id: string, name: string, status: string, currentPeriodEnd?: ?string}>
     * }
     */
    public function queryAdminActiveSubscriptionsDetailed(ShopifyStore $store): array
    {
        if (empty($store->access_token)) {
            Log::warning('[SubscriptionSync] queryAdminActiveSubscriptionsDetailed: Store has no access token', [
                'shop' => $store->shop,
            ]);

            return [
                'success' => false,
                'status' => 'QUERY_FAILED',
                'error' => 'MISSING_ACCESS_TOKEN',
                'subscriptions' => [],
            ];
        }

        $query = <<<'GRAPHQL'
            query GetCurrentAppInstallationSubscriptions {
                currentAppInstallation {
                    id
                    activeSubscriptions {
                        id
                        name
                        status
                        currentPeriodEnd
                    }
                }
            }
            GRAPHQL;

        try {
            $response = $this->shopifyService->executeGraphQL($store, $query);

            if (! empty($response['errors'])) {
                Log::warning('[SubscriptionSync] Admin GraphQL activeSubscriptions query returned errors', [
                    'shop' => $store->shop,
                    'errors' => $response['errors'],
                ]);

                return [
                    'success' => false,
                    'status' => 'QUERY_FAILED',
                    'error' => $response['errors'][0]['message'] ?? 'GraphQL error',
                    'subscriptions' => [],
                ];
            }

            $subs = $response['data']['currentAppInstallation']['activeSubscriptions'] ?? [];

            Log::debug('[SubscriptionSync] Admin GraphQL activeSubscriptions query response', [
                'shop' => $store->shop,
                'count' => count($subs),
            ]);

            if (! empty($subs)) {
                return [
                    'success' => true,
                    'status' => 'ACTIVE_FOUND',
                    'error' => null,
                    'subscriptions' => $subs,
                ];
            }

            return [
                'success' => true,
                'status' => 'NONE_FOUND',
                'error' => null,
                'subscriptions' => [],
            ];
        } catch (\Throwable $e) {
            Log::warning('[SubscriptionSync] Admin GraphQL activeSubscriptions query failed with exception', [
                'shop' => $store->shop,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'QUERY_FAILED',
                'error' => $e->getMessage(),
                'subscriptions' => [],
            ];
        }
    }

    /**
     * Query active subscriptions for the store directly from Shopify Admin GraphQL API.
     * Uses the store's authenticated OAuth access token.
     *
     * @return array<int, array{id: string, name: string, status: string, currentPeriodEnd?: ?string}>
     */
    public function queryAdminActiveSubscriptions(ShopifyStore $store): array
    {
        $detailed = $this->queryAdminActiveSubscriptionsDetailed($store);

        return $detailed['subscriptions'];
    }

    /**
     * Cancel an active subscription directly on Shopify Admin GraphQL API using store's access token.
     *
     * @return array{success: bool, status?: string, userErrors?: array<int, array<string, mixed>>, error?: string, message?: string}
     */
    public function cancelAdminSubscription(ShopifyStore $store, string $subscriptionGid): array
    {
        Log::info('[SubscriptionCancellation] cancelAdminSubscription: Executing mutation on Admin GraphQL', [
            'shop' => $store->shop,
            'subscription_gid' => $subscriptionGid,
        ]);

        $mutation = <<<'GRAPHQL'
            mutation AppSubscriptionCancel($id: ID!) {
                appSubscriptionCancel(id: $id) {
                    appSubscription {
                        id
                        status
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

        try {
            $response = $this->shopifyService->executeGraphQL($store, $mutation, [
                'id' => $subscriptionGid,
            ]);

            Log::info('[SubscriptionCancellation] cancelAdminSubscription: Admin GraphQL response', [
                'shop' => $store->shop,
                'subscription_gid' => $subscriptionGid,
                'response' => $response,
            ]);

            $userErrors = $response['data']['appSubscriptionCancel']['userErrors'] ?? [];
            if (! empty($userErrors)) {
                Log::error('[SubscriptionCancellation] cancelAdminSubscription: Shopify returned userErrors', [
                    'shop' => $store->shop,
                    'subscription_gid' => $subscriptionGid,
                    'userErrors' => $userErrors,
                ]);

                return [
                    'success' => false,
                    'error' => 'MUTATION_USER_ERROR',
                    'message' => $userErrors[0]['message'] ?? 'Shopify rejected subscription cancellation.',
                    'userErrors' => $userErrors,
                ];
            }

            $appSub = $response['data']['appSubscriptionCancel']['appSubscription'] ?? null;

            return [
                'success' => true,
                'status' => $appSub['status'] ?? 'CANCELLED',
                'userErrors' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[SubscriptionCancellation] cancelAdminSubscription: Exception executing mutation', [
                'shop' => $store->shop,
                'subscription_gid' => $subscriptionGid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'MUTATION_EXCEPTION',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Automatically resolve the active subscription GID across all available sources,
     * execute the appSubscriptionCancel mutation against Shopify (Admin API and/or Partner API),
     * update local state inside an atomic transaction, clear verification caches,
     * and output step-by-step diagnostic logs.
     *
     * @return array{
     *     success: bool,
     *     error?: string,
     *     message: string,
     *     subscription_id?: ?string,
     *     status?: string,
     *     shopify_cancelled?: bool,
     *     userErrors?: array<int, array<string, mixed>>
     * }
     */
    public function cancelActiveSubscriptionAutomatically(ShopifyStore|int $store): array
    {
        /** @var ShopifyStore $store */
        $store = $store instanceof ShopifyStore ? $store : ShopifyStore::findOrFail($store);

        Log::info('[SubscriptionCancellation] STEP 1: Starting automatic subscription cancellation', [
            'store_id' => $store->id,
            'shop' => $store->shop,
            'has_access_token' => ! empty($store->access_token),
            'current_plan_name' => $store->plan_name,
            'is_partner_configured' => $this->isPartnerApiConfigured(),
        ]);

        $subscriptionGid = null;
        $subscriptionSource = null;

        // STEP 2A: Check local database active subscription for shopify_charge_id first
        $localActiveSub = ShopSubscription::where('shop_id', $store->id)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->whereNotNull('shopify_charge_id')
            ->latest('id')
            ->first()
            ?? ShopSubscription::where('shop_id', $store->id)
                ->whereNotNull('shopify_charge_id')
                ->latest('id')
                ->first();

        if ($localActiveSub && ! empty($localActiveSub->shopify_charge_id)) {
            $subscriptionGid = (string) $localActiveSub->shopify_charge_id;
            $subscriptionSource = 'local_database';
            Log::info('[SubscriptionCancellation] STEP 2A: Found active subscription GID in local database', [
                'shop' => $store->shop,
                'subscription_id' => $subscriptionGid,
                'local_sub_id' => $localActiveSub->id,
            ]);
        }

        // STEP 2B: Query store Shopify Admin GraphQL API (currentAppInstallation.activeSubscriptions)
        if (! $subscriptionGid && ! empty($store->access_token)) {
            Log::info('[SubscriptionCancellation] STEP 2B: Querying Shopify Admin GraphQL API for activeSubscriptions', [
                'shop' => $store->shop,
            ]);

            $adminSubs = $this->queryAdminActiveSubscriptions($store);
            if (! empty($adminSubs)) {
                $firstActive = $adminSubs[0];
                $subscriptionGid = (string) ($firstActive['id'] ?? '');
                $subscriptionSource = 'shopify_admin_graphql';
                Log::info('[SubscriptionCancellation] STEP 2B: Resolved active subscription GID from Admin GraphQL', [
                    'shop' => $store->shop,
                    'subscription_id' => $subscriptionGid,
                    'name' => $firstActive['name'] ?? null,
                    'status' => $firstActive['status'] ?? null,
                ]);
            } else {
                Log::info('[SubscriptionCancellation] STEP 2B: Admin GraphQL returned zero active subscriptions on Shopify', [
                    'shop' => $store->shop,
                ]);
            }
        }

        // STEP 2C: Optional fallback to Partner API only if Partner API is explicitly configured
        if (! $subscriptionGid && $this->isPartnerApiConfigured()) {
            $appId = (string) config('shopify.app_gid', env('SHOPIFY_APP_GID'));
            $shopId = $store->shopify_shop_gid ?? $this->resolveShopGid($store);

            Log::info('[SubscriptionCancellation] STEP 2C: Querying Shopify Partner API for active subscription GID', [
                'shop' => $store->shop,
                'app_id' => $appId,
                'shop_id' => $shopId,
            ]);

            if ($shopId && $appId) {
                $query = <<<'GRAPHQL'
                    query GetActiveSubscription($appId: ID!, $shopId: ID!) {
                        activeSubscription(appId: $appId, shopId: $shopId) {
                            id
                            name
                            status
                        }
                    }
                    GRAPHQL;

                try {
                    $response = $this->executePartnerApiQuery($query, [
                        'appId' => $appId,
                        'shopId' => $shopId,
                    ]);

                    Log::debug('[SubscriptionCancellation] STEP 2C: Partner API response', [
                        'shop' => $store->shop,
                        'response' => $response,
                    ]);

                    if (! empty($response['errors'])) {
                        Log::error('[SubscriptionCancellation] STEP 2C: Partner API returned GraphQL errors', [
                            'shop' => $store->shop,
                            'errors' => $response['errors'],
                        ]);

                        return [
                            'success' => false,
                            'error' => 'GRAPHQL_QUERY_ERROR',
                            'message' => $response['errors'][0]['message'] ?? 'Failed to query active subscription from Partner API.',
                        ];
                    }

                    if (! empty($response['data']['activeSubscription']['id'])) {
                        $subscriptionGid = (string) $response['data']['activeSubscription']['id'];
                        $subscriptionSource = 'shopify_partner_api';
                        Log::info('[SubscriptionCancellation] STEP 2C: Resolved active subscription GID from Partner API', [
                            'shop' => $store->shop,
                            'subscription_id' => $subscriptionGid,
                            'name' => $response['data']['activeSubscription']['name'] ?? null,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('[SubscriptionCancellation] STEP 2C: Partner API query exception', [
                        'shop' => $store->shop,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('[SubscriptionCancellation] STEP 3: Subscription GID resolution summary', [
            'shop' => $store->shop,
            'resolved_gid' => $subscriptionGid,
            'source' => $subscriptionSource,
        ]);

        // STEP 4: Execute appSubscriptionCancel mutation on Shopify
        $shopifyCancelled = false;

        if ($subscriptionGid) {
            Log::info('[SubscriptionCancellation] STEP 4: Executing appSubscriptionCancel mutation on Shopify', [
                'shop' => $store->shop,
                'subscription_id' => $subscriptionGid,
                'source' => $subscriptionSource,
            ]);

            $cancelMutation = <<<'GRAPHQL'
                mutation AppSubscriptionCancel($id: ID!) {
                    appSubscriptionCancel(id: $id) {
                        appSubscription {
                            id
                            status
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
                GRAPHQL;

            // Primary: Execute via Admin GraphQL using store's access token
            if (! empty($store->access_token)) {
                $adminCancelResult = $this->cancelAdminSubscription($store, $subscriptionGid);
                if ($adminCancelResult['success']) {
                    $shopifyCancelled = true;
                    Log::info('[SubscriptionCancellation] STEP 4: Successfully cancelled subscription via Admin GraphQL', [
                        'shop' => $store->shop,
                        'subscription_id' => $subscriptionGid,
                        'status' => $adminCancelResult['status'] ?? 'CANCELLED',
                    ]);
                } else {
                    Log::error('[SubscriptionCancellation] STEP 4: Admin GraphQL cancellation failed', [
                        'shop' => $store->shop,
                        'subscription_id' => $subscriptionGid,
                        'result' => $adminCancelResult,
                    ]);

                    if (! empty($adminCancelResult['userErrors'])) {
                        $errorMessage = $adminCancelResult['userErrors'][0]['message'] ?? '';
                        // If already cancelled on Shopify, treat as cancelled
                        if (stripos($errorMessage, 'already cancelled') !== false || stripos($errorMessage, 'not active') !== false) {
                            $shopifyCancelled = true;
                        } else {
                            return [
                                'success' => false,
                                'error' => 'MUTATION_USER_ERROR',
                                'message' => $errorMessage ?: 'Shopify rejected cancellation.',
                                'userErrors' => $adminCancelResult['userErrors'],
                            ];
                        }
                    }
                }
            }

            // Fallback: Attempt cancellation via Partner API only if Partner API is configured
            if (! $shopifyCancelled && $this->isPartnerApiConfigured()) {
                try {
                    Log::info('[SubscriptionCancellation] STEP 4: Attempting cancellation via Partner API', [
                        'shop' => $store->shop,
                        'subscription_id' => $subscriptionGid,
                    ]);

                    $cancelResponse = $this->executePartnerApiQuery($cancelMutation, [
                        'id' => $subscriptionGid,
                    ]);

                    Log::info('[SubscriptionCancellation] STEP 4: Partner API cancellation response', [
                        'shop' => $store->shop,
                        'response' => $cancelResponse,
                    ]);

                    $userErrors = $cancelResponse['data']['appSubscriptionCancel']['userErrors'] ?? [];
                    if (! empty($userErrors)) {
                        Log::error('[SubscriptionCancellation] STEP 4: Partner API returned userErrors', [
                            'shop' => $store->shop,
                            'subscription_id' => $subscriptionGid,
                            'userErrors' => $userErrors,
                        ]);

                        return [
                            'success' => false,
                            'error' => 'MUTATION_USER_ERROR',
                            'message' => $userErrors[0]['message'] ?? 'Shopify Partner API returned mutation error.',
                            'userErrors' => $userErrors,
                        ];
                    }

                    $shopifyCancelled = true;
                } catch (\Throwable $e) {
                    Log::warning('[SubscriptionCancellation] STEP 4: Partner API cancellation exception', [
                        'shop' => $store->shop,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } else {
            Log::info('[SubscriptionCancellation] STEP 4: No active subscription GID found on Shopify to cancel; synchronizing local state', [
                'shop' => $store->shop,
            ]);
        }

        // STEP 5: Inside database transaction, update local records
        Log::info('[SubscriptionCancellation] STEP 5: Updating local database inside atomic transaction', [
            'shop' => $store->shop,
        ]);

        return DB::transaction(function () use ($store, $subscriptionGid, $shopifyCancelled): array {
            $activeSub = ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            $updatedRows = ShopSubscription::where('shop_id', $store->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update([
                    'status' => ShopSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_at_period_end' => false,
                ]);

            // Revert store plan_name to unassigned state
            $store->update(['plan_name' => null]);

            // STEP 6: Invalidate caches
            $this->invalidateStoreSubscriptionCache($store);
            Cache::forget("shopify:store:{$store->id}:subscription:verified");

            Log::info('[SubscriptionCancellation] STEP 6 & 7: Cancellation completed successfully', [
                'shop' => $store->shop,
                'subscription_id' => $subscriptionGid,
                'shopify_cancelled' => $shopifyCancelled,
                'local_subscriptions_cancelled' => $updatedRows,
                'local_subscription_id' => $activeSub?->id,
            ]);

            return [
                'success' => true,
                'subscription_id' => $subscriptionGid ?? ($activeSub ? (string) $activeSub->id : null),
                'status' => ShopSubscription::STATUS_CANCELLED,
                'shopify_cancelled' => $shopifyCancelled,
                'message' => 'Subscription cancelled successfully.',
            ];
        });
    }

    /**
     * Alias for cancelActiveSubscriptionAutomatically.
     */
    public function cancelCurrentActiveSubscription(ShopifyStore|int $store): array
    {
        return $this->cancelActiveSubscriptionAutomatically($store);
    }

    /**
     * Get the dynamic hosted pricing page URL for a store.
     */
    public function getHostedPricingUrl(ShopifyStore $store): string
    {
        return $store->getAppPricingUrl();
    }

    /**
     * Invalidate the cached subscription state for a store.
     */
    public function invalidateStoreSubscriptionCache(ShopifyStore $store): void
    {
        Cache::forget("shopify:store:{$store->id}:subscription:verified");
        Cache::forget("store:{$store->id}:subscription_state");
        Cache::forget("store:{$store->id}:active_subscription");
        Cache::forget("store:{$store->shop}:is_pro");
    }
}
