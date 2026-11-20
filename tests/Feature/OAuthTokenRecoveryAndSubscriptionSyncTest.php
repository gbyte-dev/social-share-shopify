<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Services\ShopifyPricingService;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class OAuthTokenRecoveryAndSubscriptionSyncTest extends TestCase
{
    use RefreshDatabase;

    protected string $testApiKey = 'test_api_key_recovery';

    protected string $testApiSecret = 'test_api_secret_recovery';

    protected string $testShop = 'teststore-vsbhaml7.myshopify.com';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shopify.api_key', $this->testApiKey);
        Config::set('shopify.api_secret', $this->testApiSecret);
        Config::set('shopify.app_url', 'https://socialnest.test');
        Config::set('shopify.redirect_uri', 'https://socialnest.test/shopify/callback');
        Config::set('shopify.api_scopes', 'read_products,write_themes');
        Config::set('shopify.webhook_secret', $this->testApiSecret);
        ShopifyStore::$autoSubscribeInTests = false;

        // Ensure Starter and Pro plans exist
        Plan::firstOrCreate(
            ['slug' => 'socialvire-starter'],
            [
                'name' => 'Socialvire Starter',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Socialvire Pro',
                'price' => 5.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'is_active' => true,
            ]
        );
    }

    protected function tearDown(): void
    {
        ShopifyStore::$autoSubscribeInTests = true;
        parent::tearDown();
    }

    protected function generateValidHmacParams(array $params): array
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $val) {
            $pairs[] = "{$key}={$val}";
        }
        $queryString = implode('&', $pairs);
        $params['hmac'] = hash_hmac('sha256', $queryString, $this->testApiSecret);

        return $params;
    }

    /**
     * Scenario 1: Valid access token does not trigger refresh or OAuth redirect.
     */
    public function test_01_valid_access_token_does_not_trigger_refresh_or_reauth(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token_123',
            'refresh_token' => 'shprf_valid_token_123',
            'access_token_expires_at' => now()->addHours(2),
            'refresh_token_expires_at' => now()->addDays(30),
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->assertFalse($store->requiresReauthorization());
        $this->assertTrue($store->isInstalled());

        Http::fake([
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'shop' => ['id' => 'gid://shopify/Shop/123456'],
                ],
            ]),
        ]);

        $service = app(ShopifyService::class);
        $response = $service->executeGraphQL($store, 'query { shop { id } }');

        $this->assertEquals('gid://shopify/Shop/123456', $response['data']['shop']['id']);
        // Ensure no token refresh endpoint was called
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'oauth/access_token'));
    }

    /**
     * Scenario 2: Expired access token with valid refresh token triggers successful refresh.
     */
    public function test_02_expired_access_token_with_valid_refresh_token_triggers_successful_refresh(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_expired_token',
            'refresh_token' => 'shprf_valid_refresh_token',
            'access_token_expires_at' => now()->subMinute(),
            'refresh_token_expires_at' => now()->addDays(30),
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/oauth/access_token" => Http::response([
                'access_token' => 'shpat_new_refreshed_token',
                'refresh_token' => 'shprf_rotated_refresh_token',
                'expires_in' => 3600,
                'refresh_token_expires_in' => 7776000,
            ]),
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => ['shop' => ['id' => 'gid://shopify/Shop/999']],
            ]),
        ]);

        $service = app(ShopifyService::class);
        $response = $service->executeGraphQL($store, 'query { shop { id } }');

        $this->assertEquals('gid://shopify/Shop/999', $response['data']['shop']['id']);

        $store->refresh();
        $this->assertEquals('shpat_new_refreshed_token', $store->access_token);
        $this->assertEquals('shprf_rotated_refresh_token', $store->refresh_token);
        $this->assertFalse($store->requiresReauthorization());
    }

    /**
     * Scenario 3: Expired access token with invalid refresh token marks store for reauth.
     */
    public function test_03_expired_access_token_with_invalid_refresh_token_marks_store_for_reauth(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_expired_token',
            'refresh_token' => 'shprf_dead_token',
            'access_token_expires_at' => now()->subMinute(),
            'refresh_token_expires_at' => now()->addDays(30),
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/oauth/access_token" => Http::response(
                '<html><title>unauthorized - Oauth error invalid_grant</title></html>',
                401
            ),
        ]);

        $service = app(ShopifyService::class);

        try {
            $service->executeGraphQL($store, 'query { shop { id } }');
            $this->fail('Expected RuntimeException for invalid_grant');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('invalid_grant', $e->getMessage());
        }

        $store->refresh();
        $this->assertTrue($store->requiresReauthorization());
        $this->assertFalse($store->isInstalled());
        // Existing credentials must NOT be deleted before successful re-auth
        $this->assertEquals('shpat_expired_token', $store->access_token);
        $this->assertEquals('shprf_dead_token', $store->refresh_token);
    }

    /**
     * Scenario 4: Shopify refresh endpoint returning temporary 5xx preserves credentials without marking reauth.
     */
    public function test_04_shopify_refresh_endpoint_5xx_preserves_credentials_without_marking_reauth(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_expired_token',
            'refresh_token' => 'shprf_valid_token',
            'access_token_expires_at' => now()->subMinute(),
            'refresh_token_expires_at' => now()->addDays(30),
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/oauth/access_token" => Http::response(
                'Shopify internal error',
                503
            ),
        ]);

        $service = app(ShopifyService::class);

        try {
            $service->executeGraphQL($store, 'query { shop { id } }');
            $this->fail('Expected RuntimeException for 503');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('503', $e->getMessage());
        }

        $store->refresh();
        // 5xx is temporary, must NOT mark as reauth required
        $this->assertFalse($store->reauthorization_required);
        $this->assertEquals('shpat_expired_token', $store->access_token);
    }

    /**
     * Scenario 5: Store requiring reauth is cleanly redirected to OAuth flow.
     */
    public function test_05_store_requiring_reauth_is_redirected_to_oauth_flow(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_dead_token',
            'refresh_token' => 'shprf_dead_token',
            'access_token_expires_at' => now()->subDay(),
            'refresh_token_expires_at' => now()->subDay(),
            'reauthorization_required' => true,
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->assertTrue($store->requiresReauthorization());
        $this->assertFalse($store->isInstalled());

        $params = [
            'shop' => $this->testShop,
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvdGVzdHN0b3Jl',
            'timestamp' => (string) time(),
        ];
        $signedParams = $this->generateValidHmacParams($params);

        // Accessing appEntry with reauth required initiates OAuth breakout
        $response = $this->get('/?'.http_build_query($signedParams));

        $response->assertStatus(200);
        $response->assertSee('Redirecting to Shopify');
        $response->assertSee('oauth/authorize');
    }

    /**
     * Scenario 6: Successful OAuth callback with token rotation.
     */
    public function test_06_successful_oauth_callback_rotates_tokens_and_clears_reauth_flag(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_old_dead_token',
            'reauthorization_required' => true,
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $state = Str::random(40);
        Cache::put("shopify_oauth_state_{$state}", [
            'shop' => $this->testShop,
            'host' => 'test_host_123',
        ], now()->addMinutes(15));

        Http::fake([
            "https://{$this->testShop}/admin/oauth/access_token" => Http::response([
                'access_token' => 'shpat_fresh_oauth_token',
                'refresh_token' => 'shprf_fresh_refresh_token',
                'expires_in' => 3600,
                'refresh_token_expires_in' => 7776000,
                'scope' => 'read_products,write_themes',
            ]),
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'currentAppInstallation' => [
                        'id' => 'gid://shopify/AppInstallation/1',
                        'activeSubscriptions' => [
                            [
                                'id' => 'gid://shopify/AppSubscription/777',
                                'name' => 'Socialvire Starter',
                                'status' => 'ACTIVE',
                                'currentPeriodEnd' => null,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $callbackParams = [
            'code' => 'test_auth_code_123',
            'shop' => $this->testShop,
            'state' => $state,
            'timestamp' => (string) time(),
        ];
        $signedCallbackParams = $this->generateValidHmacParams($callbackParams);

        $response = $this->get('/shopify/callback?'.http_build_query($signedCallbackParams));

        $response->assertRedirect();
        $this->assertStringContainsString('shopify/dashboard', $response->headers->get('Location'));

        $store->refresh();
        $this->assertEquals('shpat_fresh_oauth_token', $store->access_token);
        $this->assertEquals('shprf_fresh_refresh_token', $store->refresh_token);
        $this->assertFalse($store->reauthorization_required);
        $this->assertTrue($store->isInstalled());

        // Subscription synchronized immediately
        $activeSub = ShopSubscription::where('shop_id', $store->id)->where('status', 'active')->first();
        $this->assertNotNull($activeSub);
        $this->assertEquals('gid://shopify/AppSubscription/777', $activeSub->shopify_charge_id);
    }

    /**
     * Scenario 7: Active Shopify subscription maps to local plan.
     */
    public function test_07_active_shopify_subscription_is_mapped_and_synchronized_to_database(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token',
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'currentAppInstallation' => [
                        'id' => 'gid://shopify/AppInstallation/1',
                        'activeSubscriptions' => [
                            [
                                'id' => 'gid://shopify/AppSubscription/99901',
                                'name' => 'Socialvire Starter',
                                'status' => 'ACTIVE',
                                'currentPeriodEnd' => null,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $pricingService = app(ShopifyPricingService::class);
        $result = $pricingService->verifyAndSyncActiveSubscription($store);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['subscription']);
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $result['subscription']->status);
        $this->assertEquals('gid://shopify/AppSubscription/99901', $result['subscription']->shopify_charge_id);

        $store->refresh();
        $this->assertEquals('Socialvire Starter', $store->plan_name);
    }

    /**
     * Scenario 8: No active Shopify subscription results in clean unsubscribed state.
     */
    public function test_08_no_active_shopify_subscription_results_in_clean_unsubscribed_state(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token',
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'currentAppInstallation' => [
                        'id' => 'gid://shopify/AppInstallation/1',
                        'activeSubscriptions' => [],
                    ],
                ],
            ]),
        ]);

        $pricingService = app(ShopifyPricingService::class);
        $result = $pricingService->verifyAndSyncActiveSubscription($store);

        $this->assertFalse($result['success']);
        $this->assertEquals('NO_ACTIVE_SUBSCRIPTION', $result['error']);
        $this->assertEquals('NONE_FOUND', $result['status']);
        $this->assertNull($result['subscription']);
    }

    /**
     * Scenario 9: Shopify GraphQL request failure preserves existing subscription without downgrade.
     */
    public function test_09_shopify_graphql_failure_preserves_existing_subscription_without_downgrade(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token',
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
            'plan_name' => 'Socialvire Pro',
        ]);

        $proPlan = Plan::where('slug', 'pro')->first();
        $activeSub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $proPlan->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/existing_paid_123',
            'status' => ShopSubscription::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        // Simulate GraphQL failure / 500 error
        Http::fake([
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response(
                'GraphQL service timeout',
                500
            ),
        ]);

        $pricingService = app(ShopifyPricingService::class);
        $result = $pricingService->verifyAndSyncActiveSubscription($store);

        $this->assertFalse($result['success']);
        $this->assertEquals('SHOPIFY_API_FAILED', $result['error']);
        $this->assertEquals('QUERY_FAILED', $result['status']);

        // Existing active subscription must remain untouched
        $activeSub->refresh();
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $activeSub->status);
        $store->refresh();
        $this->assertEquals('Socialvire Pro', $store->plan_name);
    }

    /**
     * Scenario 10: Repeated billing page visits are idempotent and do not create duplicate records.
     */
    public function test_10_repeated_billing_page_visits_are_idempotent_and_do_not_create_duplicates(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token',
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'currentAppInstallation' => [
                        'id' => 'gid://shopify/AppInstallation/1',
                        'activeSubscriptions' => [
                            [
                                'id' => 'gid://shopify/AppSubscription/repeat_charge_1',
                                'name' => 'Socialvire Starter',
                                'status' => 'ACTIVE',
                                'currentPeriodEnd' => null,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $pricingService = app(ShopifyPricingService::class);

        // Visit 1
        $pricingService->verifyAndSyncActiveSubscription($store);
        $countAfterFirst = ShopSubscription::where('shop_id', $store->id)->count();
        $this->assertEquals(1, $countAfterFirst);

        // Visit 2
        $pricingService->verifyAndSyncActiveSubscription($store);
        $countAfterSecond = ShopSubscription::where('shop_id', $store->id)->count();
        $this->assertEquals(1, $countAfterSecond);

        // Visit 3
        $pricingService->verifyAndSyncActiveSubscription($store);
        $countAfterThird = ShopSubscription::where('shop_id', $store->id)->count();
        $this->assertEquals(1, $countAfterThird);
    }

    /**
     * Scenario 11: Duplicate webhook delivery is idempotent.
     */
    public function test_11_duplicate_webhook_delivery_is_idempotent(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token',
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $webhookPayload = [
            'app_subscription' => [
                'admin_graphql_api_id' => 'gid://shopify/AppSubscription/wh_123',
                'name' => 'Socialvire Starter',
                'status' => 'ACTIVE',
            ],
        ];
        $payloadJson = json_encode($webhookPayload);
        $hmacHeader = base64_encode(hash_hmac('sha256', $payloadJson, $this->testApiSecret, true));

        // Delivery 1
        $response1 = $this->postJson('/shopify/webhooks', $webhookPayload, [
            'X-Shopify-Topic' => 'app_subscriptions/update',
            'X-Shopify-Shop-Domain' => $this->testShop,
            'X-Shopify-Hmac-Sha256' => $hmacHeader,
        ]);
        $response1->assertStatus(200);
        $this->assertEquals(1, ShopSubscription::where('shop_id', $store->id)->count());

        // Duplicate Delivery 2
        $response2 = $this->postJson('/shopify/webhooks', $webhookPayload, [
            'X-Shopify-Topic' => 'app_subscriptions/update',
            'X-Shopify-Shop-Domain' => $this->testShop,
            'X-Shopify-Hmac-Sha256' => $hmacHeader,
        ]);
        $response2->assertStatus(200);
        $this->assertEquals(1, ShopSubscription::where('shop_id', $store->id)->count());
    }

    /**
     * Scenario 12: Existing merchant with active paid subscription is preserved during sync.
     */
    public function test_12_existing_merchant_with_active_paid_subscription_is_preserved_during_sync(): void
    {
        $store = ShopifyStore::create([
            'shop' => $this->testShop,
            'access_token' => 'shpat_valid_token',
            'scope' => 'read_products,write_themes',
            'is_active' => true,
            'installed_at' => now(),
            'plan_name' => 'Socialvire Pro',
        ]);

        $proPlan = Plan::where('slug', 'pro')->first();
        $paidSub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $proPlan->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/paid_sub_555',
            'plan_handle' => 'pro',
            'status' => ShopSubscription::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        Http::fake([
            "https://{$this->testShop}/admin/api/*/graphql.json" => Http::response([
                'data' => [
                    'currentAppInstallation' => [
                        'id' => 'gid://shopify/AppInstallation/1',
                        'activeSubscriptions' => [
                            [
                                'id' => 'gid://shopify/AppSubscription/paid_sub_555',
                                'name' => 'Socialvire Pro',
                                'status' => 'ACTIVE',
                                'currentPeriodEnd' => now()->addDays(20)->toIso8601String(),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $pricingService = app(ShopifyPricingService::class);
        $result = $pricingService->verifyAndSyncActiveSubscription($store);

        $this->assertTrue($result['success']);
        $this->assertEquals($paidSub->id, $result['subscription']->id);
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $result['subscription']->status);
        $this->assertEquals($proPlan->id, $result['subscription']->plan_id);
    }
}
