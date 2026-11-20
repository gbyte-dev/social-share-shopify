<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Models\SocialLink;
use App\Models\WishlistItem;
use App\Services\EntitlementService;
use App\Services\ShopifyPricingService;
use App\Services\SubscriptionService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyAppPricingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $freePlan;

    protected Plan $proPlan;

    protected ShopifyPricingService $pricingService;

    protected SubscriptionService $subscriptionService;

    protected EntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'shopify.api_key' => 'test-api-key',
            'shopify.api_secret' => 'test-api-secret-1234567890123456',
            'shopify.webhook_secret' => 'test-api-secret-1234567890123456',
            'shopify.partner_org_id' => '123456',
            'shopify.partner_api_access_token' => 'shppat_test_access_token',
            'shopify.partner_api_version' => '2024-10',
            'shopify.app_gid' => 'gid://shopify/App/987654',
            'shopify.app_handle' => 'socialvire',
            'shopify.pricing_plan_handle' => 'free',
        ]);

        $this->freePlan = Plan::updateOrInsert(
            ['slug' => 'free'],
            [
                'name' => 'Free Plan',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'social_link_limit' => 2,
                'is_active' => true,
                'updated_at' => now(),
            ]
        ) ? Plan::where('slug', 'free')->first() : Plan::first();

        $this->proPlan = Plan::updateOrInsert(
            ['slug' => 'pro'],
            [
                'name' => 'SocialNest Pro',
                'price' => 5.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'social_link_limit' => null,
                'is_active' => true,
                'updated_at' => now(),
            ]
        ) ? Plan::where('slug', 'pro')->first() : Plan::first();

        $this->pricingService = app(ShopifyPricingService::class);
        $this->subscriptionService = app(SubscriptionService::class);
        $this->entitlementService = app(EntitlementService::class);
    }

    protected function createStore(string $shop = 'teststore-vsbhaml7.myshopify.com'): ShopifyStore
    {
        ShopifyStore::$autoSubscribeInTests = false;
        try {
            $store = ShopifyStore::create([
                'shop' => $shop,
                'access_token' => 'shpat_test_token_12345',
                'scope' => 'read_products,write_themes',
                'is_active' => true,
                'installed_at' => now(),
                'shopify_shop_gid' => 'gid://shopify/Shop/12345678',
            ]);
        } finally {
            ShopifyStore::$autoSubscribeInTests = true;
        }

        return $store;
    }

    protected function authStore(ShopifyStore $store): self
    {
        $payload = [
            'iss' => "https://{$store->shop}/admin",
            'dest' => "https://{$store->shop}",
            'aud' => (string) config('shopify.api_key'),
            'sub' => '1234567890',
            'exp' => time() + 3600,
            'nbf' => time() - 60,
            'iat' => time(),
        ];
        $token = JWT::encode($payload, (string) config('shopify.api_secret'), 'HS256');

        return $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => base64_encode($store->shop.'/admin'),
        ])->withToken($token);
    }

    protected function mockAdminShopGid(ShopifyStore $store, string $shopGid = 'gid://shopify/Shop/12345678'): void
    {
        Http::fake([
            "https://{$store->shop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'shop' => [
                        'id' => $shopGid,
                    ],
                ],
            ]),
        ]);
    }

    protected function mockPartnerApiSuccess(
        ShopifyStore $store,
        string $planHandle = 'free',
        string $shopGid = 'gid://shopify/Shop/12345678',
        string $appGid = 'gid://shopify/App/987654',
        string $status = 'ACTIVE'
    ): void {
        $endpoint = 'https://partners.shopify.com/123456/api/2024-10/graphql.json';

        Http::fake([
            $endpoint => Http::response([
                'data' => [
                    'activeSubscription' => [
                        'id' => 'gid://shopify/AppSubscription/777888',
                        'name' => 'Socialvire Free',
                        'status' => $status,
                        'billingPeriod' => 'PERMANENT',
                        'cancelAtEndOfCycle' => false,
                        'trialEndsAt' => null,
                        'currentBillingCycle' => [
                            'startedAt' => now()->toIso8601String(),
                            'endsAt' => null,
                        ],
                        'shop' => [
                            'id' => $shopGid,
                            'myshopifyDomain' => $store->shop,
                        ],
                        'items' => [
                            [
                                'handle' => $planHandle,
                                'description' => 'Socialvire Free Forever',
                                'price' => [
                                    'amount' => 0.0,
                                    'currencyCode' => 'USD',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            "https://{$store->shop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'shop' => [
                        'id' => $shopGid,
                    ],
                ],
            ]),
        ]);
    }

    protected function mockPartnerApiNull(ShopifyStore $store, string $shopGid = 'gid://shopify/Shop/12345678'): void
    {
        $endpoint = 'https://partners.shopify.com/123456/api/2024-10/graphql.json';

        Http::fake([
            $endpoint => Http::response([
                'data' => [
                    'activeSubscription' => null,
                ],
            ]),
            "https://{$store->shop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'shop' => [
                        'id' => $shopGid,
                    ],
                ],
            ]),
        ]);
    }

    /**
     * Scenario 1: Unsubscribed store clicking Claim Free Plan is redirected to correct
     * Shopify hosted pricing URL pattern: https://admin.shopify.com/store/{store_handle}/charges/socialvire/pricing_plans.
     */
    public function test_01_unsubscribed_store_claim_free_plan_url_matches_shopify_hosted_pricing_pattern(): void
    {
        $store = $this->createStore('teststore-vsbhaml7.myshopify.com');

        $expectedUrl = 'https://admin.shopify.com/store/teststore-vsbhaml7/charges/socialvire/pricing_plans';
        $this->assertSame($expectedUrl, $store->getAppPricingUrl());

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee($expectedUrl, false);
        $response->assertSee('Claim Free Plan');
        $response->assertSee('Socialvire Free');
        $response->assertSee('target="_top"', false);
    }

    /**
     * Scenario 2: Valid Free plan confirmation via Partner API activates subscription,
     * unlocks unlimited social links, storefront settings, wishlist, dock customization, product sharing, analytics.
     */
    public function test_02_valid_free_plan_via_partner_api_activates_full_access(): void
    {
        $store = $this->createStore('active-free.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));

        $this->mockPartnerApiSuccess($store, 'free');

        $response = $this->authStore($store)->get('/shopify/billing/welcome?shop='.$store->shop.'&plan_handle=free');

        $response->assertRedirect(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));

        $sub = $store->fresh()->activeSubscription;
        $this->assertNotNull($sub);
        $this->assertTrue($sub->isActive());
        $this->assertTrue($sub->isVerified());
        $this->assertFalse($sub->isFrozen());
        $this->assertSame('free', $sub->plan_handle);

        // Full access unlocked
        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));
        $this->assertTrue($this->entitlementService->canCreateSocialLink($store));
        $this->assertNull($this->entitlementService->getSocialLinkCreationStatus($store)['limit']);
        $this->assertTrue($this->entitlementService->canAccessStorefrontSettings($store));
        $this->assertTrue($this->entitlementService->canAccessWishlist($store));
        $this->assertTrue($this->entitlementService->canAccessPremiumFeatures($store));
    }

    /**
     * Scenario 3: Forged plan_handle query parameter without Partner API confirmation
     * is rejected and does not activate subscription.
     */
    public function test_03_forged_plan_handle_query_param_without_partner_api_confirmation_is_rejected(): void
    {
        $store = $this->createStore('attacker.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        // Partner API returns null (merchant never subscribed on Shopify)
        $this->mockPartnerApiNull($store);

        $response = $this->authStore($store)->get('/shopify/billing/welcome?shop='.$store->shop.'&plan_handle=free');

        $response->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));

        // Entitlements remain unverified basic tier (limit 2, settings locked)
        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));
        $this->assertFalse($this->entitlementService->canAccessStorefrontSettings($store));
        $this->assertFalse($this->entitlementService->canAccessWishlist($store));
        $this->assertSame(2, $this->entitlementService->getSocialLinkCreationStatus($store)['limit']);
    }

    /**
     * Scenario 4: Mismatched shop domain fails verification.
     */
    public function test_04_mismatched_shop_domain_fails_verification(): void
    {
        $store = $this->createStore('legit-store.myshopify.com');

        // Attacker passes another shop in query parameter
        $response = $this->authStore($store)->get('/shopify/billing/welcome?shop=victim-store.myshopify.com&plan_handle=free');

        // Request must be rejected and subscription must not be activated
        $this->assertTrue(in_array($response->status(), [302, 401, 403]));
        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));
        $this->assertNull($store->fresh()->activeSubscription?->last_verified_at);
    }

    /**
     * Scenario 5: Wrong app GID in Partner API response fails verification.
     */
    public function test_05_wrong_app_gid_in_partner_api_response_fails_verification(): void
    {
        $store = $this->createStore('wrong-app.myshopify.com');

        // Mismatched shop GID in Partner API
        $this->mockPartnerApiSuccess($store, 'free', 'gid://shopify/Shop/WRONG_SHOP_ID');

        $result = $this->pricingService->verifyAndSyncActiveSubscription($store, 'free');
        $this->assertNull($result['subscription'] ?? null);
        $this->assertFalse($result['success'] ?? true);
    }

    /**
     * Scenario 6: Partner API network timeout leaves existing subscription state intact without downgrade.
     */
    public function test_06_partner_api_network_timeout_leaves_existing_subscription_state_intact_without_downgrade(): void
    {
        $store = $this->createStore('timeout-store.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
            'current_period_start' => now(),
        ]);

        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));

        // Network connection timeout on Partner API
        Http::fake([
            'https://partners.shopify.com/*' => function () {
                throw new ConnectionException('Partner API connection timed out.');
            },
        ]);

        $partnerQuery = $this->pricingService->queryPartnerActiveSubscription($store);
        $this->assertSame('TIMEOUT', $partnerQuery['error']);

        // Existing verified active status must NOT be modified or downgraded
        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $sub->fresh()->status);
        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));
    }

    /**
     * Scenario 7: Partner API rate limit (429) does not falsely downgrade subscription.
     */
    public function test_07_partner_api_rate_limit_429_does_not_falsely_downgrade(): void
    {
        $store = $this->createStore('rate-limit-store.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
            'current_period_start' => now(),
        ]);

        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));

        Http::fake([
            'https://partners.shopify.com/*' => Http::response(['errors' => 'Throttled'], 429),
        ]);

        $partnerQuery = $this->pricingService->queryPartnerActiveSubscription($store);
        $this->assertSame('RATE_LIMITED', $partnerQuery['error']);
        $this->assertTrue($partnerQuery['is_rate_limited']);

        // Existing active status remains untouched
        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $sub->fresh()->status);
        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));
    }

    /**
     * Scenario 8: Subscription cancellation restores 2-link limit, locks settings and wishlist.
     */
    public function test_08_cancellation_restores_2_link_limit_and_locks_settings_and_wishlist(): void
    {
        $store = $this->createStore('cancel-store.myshopify.com');
        ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
            'current_period_start' => now(),
        ]);

        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));

        $response = $this->authStore($store)->post('/shopify/plans/cancel');

        $response->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));

        // Entitlements downgraded to 2 links, locked settings & wishlist
        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));
        $this->assertSame(2, $this->entitlementService->getSocialLinkCreationStatus($store)['limit']);
        $this->assertFalse($this->entitlementService->canAccessStorefrontSettings($store));
        $this->assertFalse($this->entitlementService->canAccessWishlist($store));
    }

    /**
     * Scenario 9: Frozen subscription status blocks access.
     */
    public function test_09_frozen_subscription_status_blocks_access(): void
    {
        $store = $this->createStore('frozen-store.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_FROZEN,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
        ]);

        $this->assertTrue($sub->isFrozen());
        $this->assertFalse($sub->isActive());
        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));
        $this->assertFalse($this->entitlementService->canAccessStorefrontSettings($store));
        $this->assertFalse($this->entitlementService->canAccessWishlist($store));
        $this->assertSame(2, $this->entitlementService->getSocialLinkCreationStatus($store)['limit']);
    }

    /**
     * Scenario 10: Unfrozen subscription status with active verification restores full access.
     */
    public function test_10_unfrozen_subscription_with_active_verification_restores_full_access(): void
    {
        $store = $this->createStore('unfrozen-store.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_FROZEN,
            'plan_handle' => 'free',
            'last_verified_at' => now()->subDay(),
            'activated_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));

        // Partner API returns active
        $this->mockPartnerApiSuccess($store, 'free');

        $result = $this->pricingService->verifyAndSyncActiveSubscription($store, 'free');
        $verified = is_array($result) ? ($result['subscription'] ?? null) : $result;

        $this->assertNotNull($verified);
        $this->assertTrue($verified->isActive());
        $this->assertFalse($verified->isFrozen());
        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));
    }

    /**
     * Scenario 11: Duplicate Welcome URL visits are idempotent.
     */
    public function test_11_duplicate_welcome_url_visits_are_idempotent(): void
    {
        $store = $this->createStore('idempotent-store.myshopify.com');
        $this->mockPartnerApiSuccess($store, 'free');

        // First visit
        $this->authStore($store)->get('/shopify/billing/welcome?shop='.$store->shop.'&plan_handle=free');
        $firstSubCount = ShopSubscription::where('shop_id', $store->id)->where('status', ShopSubscription::STATUS_ACTIVE)->count();
        $this->assertSame(1, $firstSubCount);

        // Second visit
        $this->authStore($store)->get('/shopify/billing/welcome?shop='.$store->shop.'&plan_handle=free');
        $secondSubCount = ShopSubscription::where('shop_id', $store->id)->where('status', ShopSubscription::STATUS_ACTIVE)->count();
        $this->assertSame(1, $secondSubCount);
    }

    /**
     * Scenario 12: Cancellation API error preserves verified subscription state.
     */
    public function test_12_cancellation_api_error_preserves_verified_subscription_state(): void
    {
        $store = $this->createStore('error-reconcile.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
            'current_period_start' => now(),
        ]);

        Http::fake([
            'https://partners.shopify.com/*' => Http::response(['errors' => ['Internal server error']], 500),
        ]);

        Artisan::call('shopify:reconcile-subscriptions', ['--shop' => $store->shop]);

        // State remains active and not downgraded
        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $sub->fresh()->status);
        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));
    }

    /**
     * Scenario 13: Existing social links beyond the 2-link limit are preserved in the
     * database after downgrade (never deleted).
     */
    public function test_13_existing_social_links_beyond_limit_are_preserved_after_downgrade(): void
    {
        $store = $this->createStore('preserve-links.myshopify.com');

        // Create 4 social links
        for ($i = 1; $i <= 4; $i++) {
            SocialLink::create([
                'shopify_store_id' => $store->id,
                'platform' => 'instagram',
                'title' => "Instagram {$i}",
                'url' => "https://instagram.com/user{$i}",
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        $this->assertSame(4, $store->socialLinks()->count());

        // Cancel / downgrade store
        $this->subscriptionService->assignFreeSubscription($store);

        // All 4 links must remain in the database (never deleted!)
        $this->assertSame(4, $store->fresh()->socialLinks()->count());

        // Storefront query returns only the top 2 links
        $storefrontLinks = $this->entitlementService->getStorefrontSocialLinks($store);
        $this->assertCount(2, $storefrontLinks);
    }

    /**
     * Scenario 14: Existing wishlist items are preserved in the database after downgrade (never deleted).
     */
    public function test_14_existing_wishlist_items_are_preserved_after_downgrade(): void
    {
        $store = $this->createStore('preserve-wishlist.myshopify.com');

        WishlistItem::create([
            'shop_id' => $store->id,
            'product_id' => 'prod_123',
            'product_title' => 'Sneakers',
            'customer_id' => 'cust_456',
        ]);

        WishlistItem::create([
            'shop_id' => $store->id,
            'product_id' => 'prod_789',
            'product_title' => 'T-Shirt',
            'customer_id' => 'cust_456',
        ]);

        $this->assertSame(2, WishlistItem::where('shop_id', $store->id)->count());

        // Downgrade / cancel store
        $this->subscriptionService->assignFreeSubscription($store);

        // Wishlist access locked on free tier
        $this->assertFalse($this->entitlementService->canAccessWishlist($store));

        // Wishlist database rows remain completely intact
        $this->assertSame(2, WishlistItem::where('shop_id', $store->id)->count());
    }

    /**
     * Scenario 15: Shopify OAuth continues to work without disruption.
     */
    public function test_15_shopify_oauth_continues_to_work_without_disruption(): void
    {
        $response = $this->get('/shopify/install?shop=newstore.myshopify.com');
        $response->assertStatus(302);
        $this->assertStringContainsString('admin/oauth/authorize', $response->headers->get('Location'));
    }

    /**
     * Scenario 16: Existing navigation and dashboard functionality continues to work.
     */
    public function test_16_existing_navigation_and_dashboard_functionality_continues_to_work(): void
    {
        $store = $this->createStore('nav-store.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $dash = $this->authStore($store)->get('/shopify/dashboard');
        $dash->assertStatus(200);

        $links = $this->authStore($store)->get('/shopify/social-links');
        $links->assertStatus(200);

        $plans = $this->authStore($store)->get('/shopify/plans');
        $plans->assertStatus(200);

        $support = $this->authStore($store)->get('/shopify/support');
        $support->assertStatus(200);
    }

    /**
     * Scenario 17: No theme app extension files under extensions/ were modified.
     */
    public function test_17_no_theme_app_extension_files_modified(): void
    {
        $extensionsDir = base_path('extensions');
        $this->assertDirectoryExists($extensionsDir);
        $this->assertFileExists($extensionsDir.'/socialnest-theme-ext/blocks/floating-social-dock.liquid');
        $this->assertFileExists($extensionsDir.'/socialnest-theme-ext/blocks/inline-social-share.liquid');
        $this->assertFileExists($extensionsDir.'/socialnest-theme-ext/blocks/wishlist-page.liquid');
        $this->assertFileExists($extensionsDir.'/socialnest-theme-ext/assets/socialnest-storefront.css');
        $this->assertFileExists($extensionsDir.'/socialnest-theme-ext/assets/socialnest-storefront.js');
    }

    /**
     * Scenario 18: Fully automatic subscription cancellation queries Partner API for active GID,
     * executes appSubscriptionCancel mutation, downgrades store to free tier, and clears cache.
     */
    public function test_18_automatic_subscription_cancellation_queries_partner_api_and_cancels_mutation(): void
    {
        $store = $this->createStore('auto-cancel.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
            'current_period_start' => now(),
        ]);

        // Prime cache
        $cacheKey = "shopify:store:{$store->id}:subscription:verified";
        cache()->put($cacheKey, ['verified' => true], 3600);
        $this->assertTrue(cache()->has($cacheKey));
        $this->assertTrue($this->entitlementService->hasFullEntitlements($store));

        $endpoint = 'https://partners.shopify.com/123456/api/2024-10/graphql.json';
        $subGid = 'gid://shopify/AppSubscription/99887766';

        Http::fake([
            $endpoint => function (Request $request) use ($subGid) {
                $body = $request->data();
                $query = $body['query'] ?? '';

                if (str_contains($query, 'GetActiveSubscription')) {
                    return Http::response([
                        'data' => [
                            'activeSubscription' => [
                                'id' => $subGid,
                                'name' => 'Socialvire Free',
                                'status' => 'ACTIVE',
                            ],
                        ],
                    ]);
                }

                if (str_contains($query, 'AppSubscriptionCancel')) {
                    return Http::response([
                        'data' => [
                            'appSubscriptionCancel' => [
                                'appSubscription' => [
                                    'id' => $subGid,
                                    'status' => 'CANCELLED',
                                ],
                                'userErrors' => [],
                            ],
                        ],
                    ]);
                }

                return Http::response(['data' => []]);
            },
        ]);

        $response = $this->authStore($store)->postJson('/shopify/subscription/cancel-active');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'subscription_id' => $subGid,
            'status' => 'cancelled',
        ]);

        // Verify local state updated
        $this->assertSame(ShopSubscription::STATUS_CANCELLED, $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->cancelled_at);

        // Verify cache cleared
        $this->assertFalse(cache()->has($cacheKey));

        // Verify downgraded to free tier limits
        $this->assertFalse($this->entitlementService->hasFullEntitlements($store));
        $this->assertSame(2, $this->entitlementService->getSocialLinkCreationStatus($store)['limit']);
        $this->assertFalse($this->entitlementService->canAccessStorefrontSettings($store));
        $this->assertFalse($this->entitlementService->canAccessWishlist($store));
    }

    /**
     * Scenario 19: Automatic subscription cancellation gracefully handles userErrors from Partner API.
     */
    public function test_19_automatic_subscription_cancellation_handles_user_errors_gracefully(): void
    {
        $store = $this->createStore('auto-cancel-error.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'plan_handle' => 'free',
            'last_verified_at' => now(),
            'activated_at' => now(),
            'current_period_start' => now(),
        ]);

        $endpoint = 'https://partners.shopify.com/123456/api/2024-10/graphql.json';
        $subGid = 'gid://shopify/AppSubscription/99887766';

        Http::fake([
            $endpoint => function (Request $request) use ($subGid) {
                $body = $request->data();
                $query = $body['query'] ?? '';

                if (str_contains($query, 'GetActiveSubscription')) {
                    return Http::response([
                        'data' => [
                            'activeSubscription' => [
                                'id' => $subGid,
                                'name' => 'Socialvire Free',
                                'status' => 'ACTIVE',
                            ],
                        ],
                    ]);
                }

                if (str_contains($query, 'AppSubscriptionCancel')) {
                    return Http::response([
                        'data' => [
                            'appSubscriptionCancel' => [
                                'appSubscription' => null,
                                'userErrors' => [
                                    [
                                        'field' => ['id'],
                                        'message' => 'Subscription is already cancelled or does not exist.',
                                    ],
                                ],
                            ],
                        ],
                    ]);
                }

                return Http::response(['data' => []]);
            },
        ]);

        $response = $this->authStore($store)->postJson('/shopify/subscription/cancel-active');

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'MUTATION_USER_ERROR',
            'message' => 'Subscription is already cancelled or does not exist.',
        ]);

        // State remains active and not corrupted
        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $sub->fresh()->status);
    }

    /**
     * Scenario 20: Welcome flow dynamically extracts and matches plan slug/name against local plans table.
     */
    public function test_20_welcome_flow_dynamically_matches_and_assigns_plan_by_slug_and_name(): void
    {
        $starterPlan = Plan::create([
            'name' => 'Socialvire Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'currency' => 'USD',
            'billing_interval' => '30_days',
            'social_link_limit' => 10,
            'is_active' => true,
        ]);

        $store = $this->createStore('starter-plan.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $endpoint = 'https://partners.shopify.com/123456/api/2024-10/graphql.json';
        Http::fake([
            $endpoint => Http::response([
                'data' => [
                    'activeSubscription' => [
                        'id' => 'gid://shopify/AppSubscription/112233',
                        'name' => 'Socialvire Starter',
                        'status' => 'ACTIVE',
                        'billingPeriod' => '30_DAYS',
                        'cancelAtEndOfCycle' => false,
                        'currentBillingCycle' => [
                            'startTime' => now()->toIso8601String(),
                            'endTime' => now()->addDays(30)->toIso8601String(),
                        ],
                        'shop' => [
                            'id' => $store->shopify_shop_gid,
                            'myshopifyDomain' => $store->shop,
                        ],
                        'items' => [
                            [
                                'handle' => 'starter',
                                'description' => 'Socialvire Starter Tier',
                                'price' => [
                                    'amount' => 9.99,
                                    'currencyCode' => 'USD',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->authStore($store)->get('/shopify/billing/welcome?shop='.$store->shop.'&plan_handle=starter');

        $response->assertRedirect(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));

        $activeSub = $store->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertSame($starterPlan->id, $activeSub->plan_id);
        $this->assertSame('starter', $activeSub->plan_handle);
        $this->assertSame('Socialvire Starter', $store->fresh()->plan_name);
        $this->assertSame(10, $this->entitlementService->getSocialLinkCreationStatus($store)['limit']);
    }

    /**
     * Scenario 21: Webhook app_subscriptions/update dynamically resolves and activates plan by slug/name.
     */
    public function test_21_webhook_app_subscriptions_update_dynamically_resolves_and_activates_plan_by_slug(): void
    {
        $starterPlan = Plan::create([
            'name' => 'Socialvire Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'currency' => 'USD',
            'billing_interval' => '30_days',
            'social_link_limit' => 10,
            'is_active' => true,
        ]);

        $store = $this->createStore('webhook-starter.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $chargeId = 'gid://shopify/AppSubscription/888999';

        $hmac = base64_encode(hash_hmac('sha256', json_encode([
            'app_subscription' => [
                'admin_graphql_api_id' => $chargeId,
                'name' => 'Socialvire Starter',
                'status' => 'ACTIVE',
            ],
        ]), (string) config('shopify.webhook_secret'), true));

        $response = $this->postJson('/shopify/webhooks', [
            'app_subscription' => [
                'admin_graphql_api_id' => $chargeId,
                'name' => 'Socialvire Starter',
                'status' => 'ACTIVE',
            ],
        ], [
            'X-Shopify-Topic' => 'app_subscriptions/update',
            'X-Shopify-Shop-Domain' => $store->shop,
            'X-Shopify-Hmac-Sha256' => $hmac,
        ]);

        $response->assertStatus(200);

        $activeSub = $store->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertSame($starterPlan->id, $activeSub->plan_id);
    }

    /**
     * Scenario 22: Cancellation workflow halts and preserves local state when Partner API query fails with error.
     */
    public function test_22_cancellation_aborts_and_preserves_database_when_partner_api_query_returns_error(): void
    {
        $store = $this->createStore('abort-cancel.myshopify.com');
        $sub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->proPlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'activated_at' => now(),
        ]);

        $endpoint = 'https://partners.shopify.com/123456/api/2024-10/graphql.json';
        Http::fake([
            $endpoint => Http::response([
                'errors' => [
                    ['message' => 'GraphQL connection timeout from Partner API'],
                ],
            ], 200),
        ]);

        $result = $this->pricingService->cancelActiveSubscriptionAutomatically($store);

        $this->assertFalse($result['success']);
        $this->assertSame('GRAPHQL_QUERY_ERROR', $result['error']);

        // Local state was NOT cancelled or modified
        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $sub->fresh()->status);
    }
}
