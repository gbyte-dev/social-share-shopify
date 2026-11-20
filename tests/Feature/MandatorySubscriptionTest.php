<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Services\EntitlementService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MandatorySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $freePlan;

    protected Plan $proPlan;

    protected SubscriptionService $subscriptionService;

    protected EntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'shopify.api_key' => 'test-api-key',
            'shopify.api_secret' => 'test-api-secret-1234567890123456',
            'shopify.webhook_secret' => 'test-api-secret-1234567890123456',
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

        $this->subscriptionService = app(SubscriptionService::class);
        $this->entitlementService = app(EntitlementService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        ShopifyStore::$autoSubscribeInTests = true;
        parent::tearDown();
    }

    protected function createInactiveStore(string $shop = 'inactive-store.myshopify.com'): ShopifyStore
    {
        ShopifyStore::$autoSubscribeInTests = false;
        try {
            $store = ShopifyStore::create([
                'shop' => $shop,
                'access_token' => 'shpat_test_inactive_token',
                'scope' => 'read_products',
                'is_active' => true,
                'installed_at' => now(),
            ]);
        } finally {
            ShopifyStore::$autoSubscribeInTests = true;
        }

        return $store;
    }

    protected function createFreeStore(string $shop = 'free-store.myshopify.com'): ShopifyStore
    {
        $store = $this->createInactiveStore($shop);
        $this->subscriptionService->assignFreeSubscription($store);

        return $store;
    }

    protected function createActiveStore(string $shop = 'active-store.myshopify.com'): ShopifyStore
    {
        $store = $this->createInactiveStore($shop);
        $this->subscriptionService->activateSubscription($store, $this->proPlan);

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

    // ==========================================
    // 1. Subscription Plans Availability
    // ==========================================

    public function test_1_free_and_pro_subscription_plans_are_available(): void
    {
        $activePlans = Plan::where('is_active', true)->get();

        $this->assertGreaterThanOrEqual(2, $activePlans->count());
        $this->assertNotNull($activePlans->firstWhere('slug', 'free'));
        $this->assertNotNull($activePlans->firstWhere('slug', 'pro'));

        $free = $activePlans->firstWhere('slug', 'free');
        $this->assertSame(0.00, (float) $free->price);
        $this->assertSame(2, (int) $free->social_link_limit);

        $pro = $activePlans->firstWhere('slug', 'pro');
        $this->assertSame(5.00, (float) $pro->price);
        $this->assertNull($pro->social_link_limit);
    }

    // ==========================================
    // 2. Free Plan Cannot Be Subscribed Via Recurring Billing
    // ==========================================

    public function test_2_free_plan_cannot_be_activated_as_recurring_paid_charge(): void
    {
        $store = $this->createInactiveStore();

        $this->expectException(InvalidArgumentException::class);
        $this->subscriptionService->activateSubscription($store, $this->freePlan);
    }

    public function test_2b_free_plan_submission_to_paid_subscribe_is_rejected(): void
    {
        $store = $this->createInactiveStore();

        $response = $this->authStore($store)->post('/shopify/plans/subscribe', [
            'plan_id' => $this->freePlan->id,
        ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // 3. Free Merchant Access to Dashboard and General Views
    // ==========================================

    public function test_3_free_merchant_can_access_dashboard_and_general_views(): void
    {
        $store = $this->createFreeStore();

        $dash = $this->authStore($store)->get('/shopify/dashboard');
        $dash->assertStatus(200);
        $dash->assertViewIs('shopify.dashboard');

        $links = $this->authStore($store)->get('/shopify/social-links');
        $links->assertStatus(200);

        $plans = $this->authStore($store)->get('/shopify/plans');
        $plans->assertStatus(200);

        $support = $this->authStore($store)->get('/shopify/support');
        $support->assertStatus(200);
    }

    // ==========================================
    // 4. Free Merchant Blocked From Premium Features
    // ==========================================

    public function test_4_free_merchant_cannot_access_storefront_settings_or_wishlist(): void
    {
        $store = $this->createFreeStore();

        // 1. Settings view redirects to plans with upgrade warning
        $settingsRes = $this->authStore($store)->get('/shopify/settings');
        $settingsRes->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));
        $settingsRes->assertSessionHas('warning', 'This feature requires a SocialNest Pro subscription.');

        // 2. Settings API update returns 403 UPGRADE_REQUIRED
        $settingsApi = $this->authStore($store)->putJson('/shopify/settings', [
            'is_enabled' => true,
        ]);
        $settingsApi->assertStatus(403);
        $settingsApi->assertJson([
            'error' => 'UPGRADE_REQUIRED',
            'code' => 'UPGRADE_REQUIRED',
        ]);

        // 3. Wishlist status API returns 403 UPGRADE_REQUIRED
        $wishlistStatus = $this->authStore($store)->getJson('/shopify/wishlist/status');
        $wishlistStatus->assertStatus(403);
        $wishlistStatus->assertJson([
            'code' => 'UPGRADE_REQUIRED',
        ]);
    }

    // ==========================================
    // 5. Successful Activation Creates Exactly 30 Days
    // ==========================================

    public function test_5_successful_activation_creates_exactly_30_days(): void
    {
        $startTime = Carbon::parse('2026-11-01 10:00:00');
        Carbon::setTestNow($startTime);

        $store = $this->createInactiveStore();
        $sub = $this->subscriptionService->activateSubscription($store, $this->proPlan);

        $this->assertEquals($startTime->toIso8601String(), $sub->activated_at->toIso8601String());
        $this->assertEquals($startTime->toIso8601String(), $sub->current_period_start->toIso8601String());
        $this->assertEquals($startTime->copy()->addDays(30)->toIso8601String(), $sub->current_period_end->toIso8601String());
        $this->assertEquals(30, $sub->current_period_start->diffInDays($sub->current_period_end));
        $this->assertTrue($this->subscriptionService->isActive($store));
        $this->assertTrue($this->entitlementService->isPro($store));
    }

    // ==========================================
    // 6. Exact Expiry of Pro Subscription Resolves to Free
    // ==========================================

    public function test_6_exact_expiry_of_pro_subscription_resolves_to_free(): void
    {
        $startTime = Carbon::parse('2026-11-01 12:00:00');
        Carbon::setTestNow($startTime);

        $store = $this->createActiveStore();
        $sub = $store->activeSubscription;

        // Move to current_period_end timestamp
        Carbon::setTestNow($sub->current_period_end->copy()->addSecond());

        // Pro entitlement is expired
        $this->assertFalse($this->entitlementService->isPro($store));

        // Falls back to unassigned Free tier limits without ghost subscription
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->subscriptionService->isActive($store));
        $this->assertNull($this->subscriptionService->getActiveSubscription($store));

        // Free access retained for Dashboard
        $dash = $this->authStore($store)->get('/shopify/dashboard');
        $dash->assertStatus(200);

        // Pro settings view blocked
        $settings = $this->authStore($store)->get('/shopify/settings');
        $settings->assertRedirect();
    }

    // ==========================================
    // 7. Permanent Free Subscription Does Not Expire
    // ==========================================

    public function test_7_permanent_free_subscription_does_not_expire(): void
    {
        $startTime = Carbon::parse('2026-11-01 12:00:00');
        Carbon::setTestNow($startTime);

        $store = $this->createFreeStore();
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));

        // Advance 365 days
        Carbon::setTestNow($startTime->copy()->addDays(365));

        $this->assertTrue($this->subscriptionService->isActive($store));
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));
    }

    // ==========================================
    // 8. Active Pro Subscription Grants Access to All Features
    // ==========================================

    public function test_8_active_pro_subscription_grants_access_to_all_features(): void
    {
        $store = $this->createActiveStore();

        $response = $this->authStore($store)->get('/shopify/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');

        $settings = $this->authStore($store)->get('/shopify/settings');
        $settings->assertStatus(200);

        $links = $this->authStore($store)->get('/shopify/social-links');
        $links->assertStatus(200);
    }

    // ==========================================
    // 9. Active Pro Subscription Can Be Cancelled and Reverts to Free
    // ==========================================

    public function test_9_active_subscription_can_be_cancelled_and_reverts_to_free(): void
    {
        $store = $this->createActiveStore();

        $response = $this->authStore($store)->post('/shopify/plans/cancel');

        $response->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));

        $sub = $store->subscriptions()->where('plan_id', $this->proPlan->id)->latest('id')->first();
        $this->assertNotNull($sub);
        $this->assertEquals(ShopSubscription::STATUS_CANCELLED, $sub->status);
        $this->assertNotNull($sub->cancelled_at);

        // Store is now cleanly unlinked from active subscription without ghost free subscription
        $this->assertFalse($this->subscriptionService->isActive($store));
        $this->assertNull($this->subscriptionService->getActiveSubscription($store));
        $this->assertNull($store->fresh()->plan_name);
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));
    }

    // ==========================================
    // 10. Cancellation Modal in Plans View
    // ==========================================

    public function test_10_cancellation_modal_is_present_in_view(): void
    {
        $store = $this->createActiveStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Cancel your subscription?');
        $response->assertSee('Keep subscription');
        $response->assertSee('Confirm cancellation');
    }

    // ==========================================
    // 11. Cancelled Pro Subscription Retains Free Access but Blocks Pro
    // ==========================================

    public function test_11_cancelled_subscription_retains_free_access_but_blocks_pro(): void
    {
        $store = $this->createActiveStore();
        $this->subscriptionService->cancelSubscription($store);

        // Store remains under unverified Free tier fallback without active subscription record
        $this->assertFalse($this->subscriptionService->isActive($store));
        $this->assertNull($this->subscriptionService->getActiveSubscription($store));
        $this->assertTrue($this->entitlementService->isFree($store));
        $this->assertFalse($this->entitlementService->isPro($store));

        // Free merchant can still access Dashboard
        $dash = $this->authStore($store)->get('/shopify/dashboard');
        $dash->assertStatus(200);

        // Pro settings view is blocked
        $settings = $this->authStore($store)->get('/shopify/settings');
        $settings->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));
    }

    // ==========================================
    // 12. Cancelled Pro Subscription Dynamically Adjusts Storefront API
    // ==========================================

    public function test_12_cancelled_pro_subscription_dynamically_adjusts_storefront_api(): void
    {
        $store = $this->createActiveStore();
        $store->getStorefrontSettings()->update(['is_enabled' => true]);

        $this->subscriptionService->cancelSubscription($store);

        $configResponse = $this->getJson("/api/storefront/config?shop={$store->shop}");
        $configResponse->assertStatus(200);
        $configResponse->assertJson([
            'success' => true,
            'enabled' => true,
            'settings' => [
                'is_pro' => false,
                'product_share_enabled' => false,
                'floating_cta_enabled' => false,
            ],
        ]);
    }

    // ==========================================
    // 13. Re-Subscribe After Cancellation Restores Pro Access
    // ==========================================

    public function test_13_resubscribe_after_cancellation_restores_pro_access(): void
    {
        $store = $this->createActiveStore();
        $this->subscriptionService->cancelSubscription($store);
        $this->assertFalse($this->entitlementService->isPro($store));

        $newSub = $this->subscriptionService->activateSubscription($store, $this->proPlan);
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $newSub->status);
        $this->assertTrue($this->entitlementService->isPro($store));

        $settings = $this->authStore($store)->get('/shopify/settings');
        $settings->assertStatus(200);
    }

    // ==========================================
    // 14. Cross-Shop Cancellation Is Rejected
    // ==========================================

    public function test_14_cross_shop_cancellation_is_rejected(): void
    {
        $storeA = $this->createActiveStore('store-a.myshopify.com');
        $storeB = $this->createActiveStore('store-b.myshopify.com');

        // Parameter spoofing with another store's domain is rejected by VerifyShopifyIdToken
        $spoofRes = $this->authStore($storeA)->post('/shopify/plans/cancel', [
            'shop' => $storeB->shop,
        ]);
        $this->assertSame(401, $spoofRes->status());

        // Attempting to cancel by passing store B's ID cannot affect store B
        $this->authStore($storeA)->post('/shopify/plans/cancel', [
            'shop_id' => $storeB->id,
            'shop' => $storeA->shop,
        ]);

        $subA = $storeA->subscriptions()->where('plan_id', $this->proPlan->id)->latest('id')->first();
        $subB = $storeB->subscriptions()->where('plan_id', $this->proPlan->id)->latest('id')->first();

        // Only store A's subscription was cancelled; store B remains untouched and active
        $this->assertEquals(ShopSubscription::STATUS_CANCELLED, $subA->status);
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $subB->status);
        $this->assertTrue($this->entitlementService->isPro($storeB));
    }

    // ==========================================
    // 15. Active Storefront Remains Functional
    // ==========================================

    public function test_15_active_storefront_remains_functional(): void
    {
        $store = $this->createActiveStore();
        $store->getStorefrontSettings()->update(['is_enabled' => true]);

        $response = $this->getJson("/api/storefront/config?shop={$store->shop}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'enabled' => true,
        ]);
    }

    // ==========================================
    // 16. Shopify OAuth and Webhooks Continue Working
    // ==========================================

    public function test_16_shopify_oauth_and_uninstall_webhooks_continue_working(): void
    {
        $install = $this->get('/shopify/install?shop=test.myshopify.com');
        $install->assertRedirect();

        $home = $this->get('/');
        $home->assertStatus(200);

        $webhook = $this->postJson('/shopify/webhooks', [
            'shop_domain' => 'test.myshopify.com',
        ], ['X-Shopify-Topic' => 'app/uninstalled', 'X-Shopify-Hmac-Sha256' => 'dummy']);

        $this->assertNotEquals(302, $webhook->status());
    }

    // ==========================================
    // 17. Resume Endpoint Informs Merchant That Cancellation Is Instant
    // ==========================================

    public function test_17_resume_subscription_informs_merchant_cancellation_is_instant(): void
    {
        $store = $this->createActiveStore();

        $this->subscriptionService->cancelSubscription($store);

        $response = $this->authStore($store)->post('/shopify/plans/resume');
        $response->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));
        $response->assertSessionHas('warning', 'Please re-subscribe to activate a new billing period.');
    }
}
