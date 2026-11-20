<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Models\WishlistItem;
use App\Services\EntitlementService;
use App\Services\ShopifyBillingService;
use App\Services\ShopifyService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionSystemTierTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $freePlan;

    protected Plan $proPlan;

    protected SubscriptionService $subscriptionService;

    protected EntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database has active Free and Pro plans
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

        $this->subscriptionService = app(SubscriptionService::class);
        $this->entitlementService = app(EntitlementService::class);

        config([
            'shopify.api_key' => 'test-api-key',
            'shopify.api_secret' => 'test-api-secret-key-1234567890123456',
            'shopify.webhook_secret' => 'test-api-secret-key-1234567890123456',
        ]);
    }

    protected function tearDown(): void
    {
        ShopifyStore::$autoSubscribeInTests = true;
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Helper to authenticate as a given store using session and mock App Bridge JWT.
     */
    protected function actingAsShop(ShopifyStore $store): self
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
            'shopify_host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ])->withToken($token);
    }

    /**
     * Create a basic installed store.
     */
    protected function createStore(string $shop = 'test-merchant.myshopify.com'): ShopifyStore
    {
        ShopifyStore::$autoSubscribeInTests = false;

        $store = ShopifyStore::create([
            'shop' => $shop,
            'access_token' => 'shpua_test_access_token_12345',
            'scope' => 'read_products,write_content',
            'is_active' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ]);

        ShopifyStore::$autoSubscribeInTests = true;

        return $store;
    }

    // ==========================================
    // 1. SUBSCRIPTION FOUNDATION & LIFECYCLE
    // ==========================================

    public function test_free_plan_is_active_and_has_zero_price(): void
    {
        $this->assertTrue($this->freePlan->is_active);
        $this->assertTrue($this->freePlan->isFree());
        $this->assertSame(0.00, (float) $this->freePlan->price);
        $this->assertSame(2, $this->freePlan->getSocialLinkLimit());
    }

    public function test_permanent_free_subscription_is_active_and_does_not_expire(): void
    {
        $store = $this->createStore('permanent-free.myshopify.com');
        $sub = $this->subscriptionService->assignFreeSubscription($store);

        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $sub->status);
        $this->assertNull($sub->current_period_end);
        $this->assertTrue($sub->isPermanentFree());
        $this->assertTrue($sub->isActive());
        $this->assertFalse($sub->isExpired());
        $this->assertTrue($this->subscriptionService->isActive($store));
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));
    }

    public function test_new_installation_remains_unassigned_without_automatic_free_plan(): void
    {
        $shopifyService = app(ShopifyService::class);

        ShopifyStore::$autoSubscribeInTests = false;
        try {
            $store = $shopifyService->persistInstalledStore('fresh-install.myshopify.com', [
                'access_token' => 'token-123456',
                'scope' => 'read_products',
            ]);
        } finally {
            ShopifyStore::$autoSubscribeInTests = true;
        }

        $this->assertDatabaseMissing('shop_subscriptions', [
            'shop_id' => $store->id,
        ]);
        $this->assertNull($this->subscriptionService->getActiveSubscription($store));
        $this->assertFalse($this->subscriptionService->isActive($store));
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));
    }

    public function test_repeated_installation_does_not_duplicate_subscriptions(): void
    {
        $shopifyService = app(ShopifyService::class);

        ShopifyStore::$autoSubscribeInTests = false;
        try {
            $store1 = $shopifyService->persistInstalledStore('repeat-install.myshopify.com', [
                'access_token' => 'token-123',
            ]);

            $store2 = $shopifyService->persistInstalledStore('repeat-install.myshopify.com', [
                'access_token' => 'token-456',
            ]);
        } finally {
            ShopifyStore::$autoSubscribeInTests = true;
        }

        $this->assertSame($store1->id, $store2->id);
        $this->assertSame(0, ShopSubscription::where('shop_id', $store1->id)->count());
    }

    public function test_valid_pro_subscription_survives_reinstall_without_downgrade(): void
    {
        $store = $this->createStore('pro-merchant.myshopify.com');
        $this->subscriptionService->activateSubscription($store, $this->proPlan, 'gid://shopify/AppSubscription/999');

        $this->assertTrue($this->entitlementService->isPro($store));

        // Merchant re-authorizes app
        $shopifyService = app(ShopifyService::class);
        $shopifyService->persistInstalledStore('pro-merchant.myshopify.com', [
            'access_token' => 'refreshed-token',
        ]);

        // Must still be Pro
        $this->assertTrue($this->entitlementService->isPro($store));
        $this->assertSame(1, ShopSubscription::where('shop_id', $store->id)->where('status', ShopSubscription::STATUS_ACTIVE)->count());
    }

    public function test_expired_pro_subscription_resolves_to_free(): void
    {
        $store = $this->createStore('expired-pro.myshopify.com');
        $sub = $this->subscriptionService->activateSubscription($store, $this->proPlan, 'gid://shopify/AppSubscription/777');

        // Move current_period_end into the past
        $sub->update([
            'current_period_start' => now()->subDays(31),
            'current_period_end' => now()->subMinute(),
        ]);

        $this->assertFalse($this->entitlementService->isPro($store));
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->subscriptionService->isActive($store));
        $this->assertNull($this->subscriptionService->getActiveSubscription($store));
    }

    // ==========================================
    // 2. SOCIAL LINKS ENFORCEMENT & LIMITS
    // ==========================================

    public function test_free_store_can_create_up_to_two_social_links(): void
    {
        $store = $this->createStore('free-links.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        // Link 1: Built-in
        $res1 = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/1234567890',
        ]);
        $res1->assertSessionHasNoErrors();
        $this->assertSame(1, $store->socialLinks()->count());

        // Link 2: Built-in
        $res2 = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'instagram',
            'url' => 'https://instagram.com/mybrand',
        ]);
        $res2->assertSessionHasNoErrors();
        $this->assertSame(2, $store->socialLinks()->count());

        // Link 3: Rejected by limit
        $res3 = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'facebook',
            'url' => 'https://facebook.com/mybrand',
        ]);
        $res3->assertSessionHasErrors('limit');
        $this->assertSame(2, $store->socialLinks()->count());
    }

    public function test_inactive_social_links_count_toward_free_limit(): void
    {
        $store = $this->createStore('inactive-counts.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        // Create 1 active and 1 inactive link directly
        $store->socialLinks()->create([
            'platform' => 'whatsapp',
            'platform_type' => 'builtin',
            'url' => 'https://wa.me/1111',
            'label' => 'WhatsApp',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $store->socialLinks()->create([
            'platform' => 'instagram',
            'platform_type' => 'builtin',
            'url' => 'https://instagram.com/test',
            'label' => 'Instagram',
            'is_active' => false, // Inactive!
            'sort_order' => 2,
        ]);

        $this->assertSame(2, $store->socialLinks()->count());
        $this->assertFalse($this->entitlementService->canCreateSocialLink($store));

        // Attempt to create third link
        $res = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'youtube',
            'url' => 'https://youtube.com/@channel',
        ]);
        $res->assertSessionHasErrors('limit');
    }

    public function test_existing_links_remain_manageable_after_downgrade(): void
    {
        $store = $this->createStore('manageable-downgrade.myshopify.com');
        $this->subscriptionService->activateSubscription($store, $this->proPlan);

        // Create 3 links on Pro
        $l1 = $store->socialLinks()->create(['platform' => 'whatsapp', 'url' => 'https://wa.me/1', 'is_active' => true, 'sort_order' => 1]);
        $l2 = $store->socialLinks()->create(['platform' => 'instagram', 'url' => 'https://instagram.com/2', 'is_active' => true, 'sort_order' => 2]);
        $l3 = $store->socialLinks()->create(['platform' => 'youtube', 'url' => 'https://youtube.com/3', 'is_active' => true, 'sort_order' => 3]);

        $this->assertSame(3, $store->socialLinks()->count());

        // Cancel Pro -> reverts to Free
        $this->subscriptionService->cancelSubscription($store);
        $this->assertTrue($this->entitlementService->isFree($store));

        // 1. All 3 links preserved
        $this->assertSame(3, $store->socialLinks()->count());

        // 2. Can edit existing link
        $editRes = $this->actingAsShop($store)->put("/shopify/social-links/{$l1->id}", [
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/999999',
            'label' => 'Updated WhatsApp',
        ]);
        $editRes->assertSessionHasNoErrors();
        $this->assertSame('https://wa.me/999999', $l1->fresh()->url);

        // 3. Can toggle existing link
        $toggleRes = $this->actingAsShop($store)->post("/shopify/social-links/{$l2->id}/toggle");
        $toggleRes->assertStatus(302);
        $this->assertFalse((bool) $l2->fresh()->is_active);

        // 4. Can reorder links
        $reorderRes = $this->actingAsShop($store)->post('/shopify/social-links/reorder', [
            'order' => [$l3->id => 1, $l1->id => 2],
        ]);
        $reorderRes->assertSessionHasNoErrors();

        // 5. Can delete link
        $delRes = $this->actingAsShop($store)->delete("/shopify/social-links/{$l3->id}");
        $delRes->assertSessionHasNoErrors();
        $this->assertSame(2, $store->socialLinks()->count());
    }

    public function test_free_storefront_api_returns_at_most_two_links_and_disables_pro_features(): void
    {
        $store = $this->createStore('storefront-free.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        // Create 3 active links
        $store->socialLinks()->create(['platform' => 'whatsapp', 'url' => 'https://wa.me/1', 'is_active' => true, 'sort_order' => 1]);
        $store->socialLinks()->create(['platform' => 'instagram', 'url' => 'https://instagram.com/2', 'is_active' => true, 'sort_order' => 2]);
        $store->socialLinks()->create(['platform' => 'youtube', 'url' => 'https://youtube.com/3', 'is_active' => true, 'sort_order' => 3]);

        $res = $this->getJson("/api/storefront/config?shop={$store->shop}");

        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
            'enabled' => true,
            'is_pro' => false,
            'settings' => [
                'product_share_enabled' => false,
                'floating_cta_enabled' => false,
                'cta_enabled' => false,
            ],
        ]);

        $data = $res->json();
        $this->assertCount(2, $data['links']);
        $this->assertSame('whatsapp', $data['links'][0]['platform']);
        $this->assertSame('instagram', $data['links'][1]['platform']);
    }

    // ==========================================
    // 3. STOREFRONT SETTINGS PROTECTION
    // ==========================================

    public function test_free_merchant_cannot_access_storefront_settings_page(): void
    {
        $store = $this->createStore('no-settings.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $res = $this->actingAsShop($store)->get('/shopify/settings');

        $res->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]));
        $res->assertSessionHas('warning');
    }

    public function test_free_merchant_cannot_update_storefront_settings_via_api(): void
    {
        $store = $this->createStore('no-settings-api.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $res = $this->actingAsShop($store)->json('PUT', '/shopify/settings', [
            'position' => 'bottom_left',
        ]);

        $res->assertStatus(403);
        $res->assertJson([
            'error' => 'UPGRADE_REQUIRED',
            'code' => 'UPGRADE_REQUIRED',
        ]);
    }

    public function test_pro_merchant_can_access_and_update_storefront_settings(): void
    {
        $store = $this->createStore('pro-settings.myshopify.com');
        $this->subscriptionService->activateSubscription($store, $this->proPlan);

        $resGet = $this->actingAsShop($store)->get('/shopify/settings');
        $resGet->assertStatus(200);

        $resPut = $this->actingAsShop($store)->json('PUT', '/shopify/settings', [
            'position' => 'vertical_right',
        ]);
        $resPut->assertStatus(200);
        $this->assertSame('vertical_right', $store->storefrontSettings()->first()->position);
    }

    // ==========================================
    // 4. WISHLIST BACKEND & STOREFRONT PROTECTION
    // ==========================================

    public function test_free_merchant_cannot_access_wishlist_setup_or_status(): void
    {
        $store = $this->createStore('wishlist-free.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $resSetup = $this->actingAsShop($store)->json('POST', '/shopify/wishlist/setup');
        $resSetup->assertStatus(403);
        $resSetup->assertJson(['code' => 'UPGRADE_REQUIRED']);

        $resStatus = $this->actingAsShop($store)->json('GET', '/shopify/wishlist/status');
        $resStatus->assertStatus(403);
        $resStatus->assertJson(['code' => 'UPGRADE_REQUIRED']);
    }

    public function test_free_storefront_cannot_access_wishlist_app_proxy(): void
    {
        $store = $this->createStore('proxy-free.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $timestamp = (string) time();
        $params = [
            'guest_uuid' => '1234567890abcdef1234',
            'shop' => $store->shop,
            'timestamp' => $timestamp,
        ];
        ksort($params);
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = "{$k}={$v}";
        }
        $signature = hash_hmac('sha256', implode('', $parts), (string) config('shopify.api_secret'));
        $params['signature'] = $signature;

        $res = $this->call(
            'GET',
            '/shopify/proxy/wishlist',
            $params
        );

        $res->assertStatus(403);
        $res->assertJson([
            'enabled' => false,
            'code' => 'UPGRADE_REQUIRED',
        ]);
    }

    public function test_wishlist_records_preserved_on_downgrade_and_restored_on_reactivation(): void
    {
        $store = $this->createStore('wishlist-restore.myshopify.com');
        $this->subscriptionService->activateSubscription($store, $this->proPlan);

        // Create wishlist record
        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => '123456',
            'product_id' => 'prod-999',
            'variant_id' => 'var-111',
            'product_handle' => 'cool-item',
            'product_title' => 'Cool Item',
        ]);

        $this->assertSame(1, WishlistItem::where('shop_id', $store->id)->count());

        // Downgrade to Free
        $this->subscriptionService->cancelSubscription($store);
        $this->assertTrue($this->entitlementService->isFree($store));

        // Record is preserved
        $this->assertSame(1, WishlistItem::where('shop_id', $store->id)->count());

        // Reactivate Pro
        $this->subscriptionService->activateSubscription($store, $this->proPlan);
        $this->assertTrue($this->entitlementService->isPro($store));

        // Record still intact and accessible
        $this->assertSame(1, WishlistItem::where('shop_id', $store->id)->count());
    }

    // ==========================================
    // 5. SHOPIFY BILLING VERIFICATION & WEBHOOKS
    // ==========================================

    public function test_billing_callback_requires_verified_active_status_to_activate_pro(): void
    {
        $store = $this->createStore('billing-verify.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        // Mock ShopifyBillingService to return ACTIVE
        $mockBilling = $this->createMock(ShopifyBillingService::class);
        $mockBilling->expects($this->once())
            ->method('getSubscriptionStatus')
            ->with($this->anything(), 'gid://shopify/AppSubscription/12345')
            ->willReturn([
                'id' => 'gid://shopify/AppSubscription/12345',
                'name' => 'SocialNest Pro',
                'status' => 'ACTIVE',
                'currentPeriodEnd' => now()->addDays(30)->toIso8601String(),
            ]);

        $this->app->instance(ShopifyBillingService::class, $mockBilling);

        $res = $this->actingAsShop($store)->get('/shopify/billing/callback?charge_id=gid://shopify/AppSubscription/12345');

        $res->assertRedirect(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]));
        $this->assertTrue($this->entitlementService->isPro($store));
        $this->assertDatabaseHas('shop_subscriptions', [
            'shop_id' => $store->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/12345',
            'status' => ShopSubscription::STATUS_ACTIVE,
        ]);
    }

    public function test_billing_callback_rejects_declined_or_inactive_status(): void
    {
        $store = $this->createStore('billing-declined.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $mockBilling = $this->createMock(ShopifyBillingService::class);
        $mockBilling->expects($this->once())
            ->method('getSubscriptionStatus')
            ->willReturn([
                'id' => 'gid://shopify/AppSubscription/declined',
                'name' => 'SocialNest Pro',
                'status' => 'DECLINED',
                'currentPeriodEnd' => null,
            ]);

        $this->app->instance(ShopifyBillingService::class, $mockBilling);

        $res = $this->actingAsShop($store)->get('/shopify/billing/callback?charge_id=gid://shopify/AppSubscription/declined');

        $res->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]));
        $this->assertTrue($this->entitlementService->isFree($store));
    }

    public function test_webhook_cancellation_downgrades_to_free(): void
    {
        $store = $this->createStore('webhook-cancel.myshopify.com');
        $this->subscriptionService->activateSubscription($store, $this->proPlan, 'gid://shopify/AppSubscription/cancel-me');

        $this->assertTrue($this->entitlementService->isPro($store));

        $payload = [
            'app_subscription' => [
                'admin_graphql_api_id' => 'gid://shopify/AppSubscription/cancel-me',
                'status' => 'CANCELLED',
            ],
            'shop' => $store->shop,
        ];
        $rawPayload = json_encode($payload);
        $hmac = base64_encode(hash_hmac('sha256', $rawPayload, config('shopify.api_secret'), true));

        $res = $this->call(
            'POST',
            '/shopify/webhooks',
            [],
            [],
            [],
            [
                'HTTP_X-Shopify-Hmac-Sha256' => $hmac,
                'HTTP_X-Shopify-Topic' => 'app_subscriptions/update',
                'HTTP_X-Shopify-Shop-Domain' => $store->shop,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $res->assertStatus(200);
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertNull($store->fresh()->activeSubscription);
        $this->assertNull($store->fresh()->plan_name);
        $this->assertSame(ShopSubscription::STATUS_CANCELLED, $store->fresh()->subscriptions()->latest('id')->first()->status);
    }

    public function test_free_merchant_confirming_dummy_subscription_activates_pro_with_null_charge_id(): void
    {
        $store = $this->createStore('dummy-confirm.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);
        $this->assertTrue($this->entitlementService->isFree($store));

        $res = $this->actingAsShop($store)->post('/shopify/plans/subscribe', [
            'plan_id' => $this->proPlan->id,
            'shop' => $store->shop,
        ]);

        $res->assertRedirect(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]));

        $this->assertTrue($this->entitlementService->isPro($store));
        $this->assertSame('SocialNest Pro', $store->fresh()->plan_name);

        $activeSub = $this->subscriptionService->getActiveSubscription($store);
        $this->assertNotNull($activeSub);
        $this->assertSame($this->proPlan->id, $activeSub->plan_id);
        $this->assertSame(ShopSubscription::STATUS_ACTIVE, $activeSub->status);
        $this->assertNull($activeSub->shopify_charge_id);
        $this->assertNotNull($activeSub->current_period_start);
        $this->assertNotNull($activeSub->current_period_end);
        $this->assertEquals(30, (int) round($activeSub->current_period_start->diffInDays($activeSub->current_period_end)));
    }

    public function test_free_merchant_client_navigation_to_storefront_settings_redirects_with_warning(): void
    {
        $store = $this->createStore('spa-settings-redirect.myshopify.com');
        $this->subscriptionService->assignFreeSubscription($store);

        $res = $this->actingAsShop($store)->get('/shopify/settings', [
            'X-Shopify-Navigation' => '1',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $res->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]));
        $res->assertSessionHas('warning', 'This feature requires a SocialNest Pro subscription.');
    }

    public function test_pro_merchant_client_navigation_to_storefront_settings_renders_view(): void
    {
        $store = $this->createStore('spa-settings-pro.myshopify.com');
        $this->subscriptionService->activateSubscription($store, $this->proPlan);

        $res = $this->actingAsShop($store)->get('/shopify/settings', [
            'X-Shopify-Navigation' => '1',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $res->assertStatus(200);
        $res->assertViewIs('shopify.settings.index');
    }

    public function test_unassigned_store_without_active_plan_enforces_strict_two_links_and_locked_features(): void
    {
        ShopifyStore::$autoSubscribeInTests = false;
        try {
            $store = $this->createStore('unassigned-tier.myshopify.com');
        } finally {
            ShopifyStore::$autoSubscribeInTests = true;
        }

        // 1. Subscription state is completely unassigned / null
        $this->assertNull($this->subscriptionService->getActiveSubscription($store));
        $this->assertFalse($this->subscriptionService->isActive($store));
        $this->assertNull($store->fresh()->plan_name);
        $this->assertSame(0, ShopSubscription::where('shop_id', $store->id)->count());

        // 2. Entitlement falls back strictly to unverified / free tier limits
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));

        $status = $this->entitlementService->getSocialLinkCreationStatus($store);
        $this->assertSame(2, $status['limit']);
        $this->assertTrue($status['can_create']);
        $this->assertFalse($status['is_pro']);

        // 3. Can add exactly 2 social links
        $res1 = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/1234567890',
        ]);
        $res1->assertSessionHasNoErrors();

        $res2 = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'instagram',
            'url' => 'https://instagram.com/mybrand',
        ]);
        $res2->assertSessionHasNoErrors();
        $this->assertSame(2, $store->socialLinks()->count());

        // 4. Attempting to add 3rd link is rejected by limit
        $res3 = $this->actingAsShop($store)->post('/shopify/social-links', [
            'platform' => 'facebook',
            'url' => 'https://facebook.com/mybrand',
        ]);
        $res3->assertSessionHasErrors('limit');
        $this->assertSame(2, $store->socialLinks()->count());

        // 5. Settings and Wishlist are locked
        $settingsRes = $this->actingAsShop($store)->get('/shopify/settings');
        $settingsRes->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]));

        $wishlistRes = $this->actingAsShop($store)->getJson('/shopify/wishlist/status');
        $wishlistRes->assertStatus(403);
        $wishlistRes->assertJson(['code' => 'UPGRADE_REQUIRED']);

        // 6. Storefront API displays top 2 links only and disables pro features
        $sfRes = $this->getJson("/api/storefront/config?shop={$store->shop}");
        $sfRes->assertStatus(200);
        $sfRes->assertJson([
            'success' => true,
            'enabled' => true,
            'settings' => [
                'is_pro' => false,
                'product_share_enabled' => false,
                'floating_cta_enabled' => false,
            ],
        ]);
        $this->assertCount(2, $sfRes->json('links'));

        // 7. Plans page shows unassigned state prompting merchant to claim plan
        $plansRes = $this->actingAsShop($store)->get('/shopify/plans');
        $plansRes->assertStatus(200);
        $plansRes->assertSee('No Active Plan');
        $plansRes->assertSee('Claim Free Plan');
    }
}
