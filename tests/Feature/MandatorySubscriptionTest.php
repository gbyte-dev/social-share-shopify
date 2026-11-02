<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MandatorySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $freePlan;

    protected Plan $proPlan;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freePlan = Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'description' => 'Legacy free plan',
                'features' => ['Basic links'],
                'is_active' => false,
            ]
        );
        $this->freePlan->update(['is_active' => false]);

        $this->proPlan = Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'SocialNest Pro',
                'price' => 5.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'description' => 'Complete storefront engagement suite',
                'features' => ['All social platforms', 'Custom color picker', 'Analytics'],
                'is_active' => true,
            ]
        );
        $this->proPlan->update([
            'name' => 'SocialNest Pro',
            'price' => 5.00,
            'is_active' => true,
        ]);

        $this->subscriptionService = app(SubscriptionService::class);
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
        $store = ShopifyStore::create([
            'shop' => $shop,
            'access_token' => 'shpat_test_inactive_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);
        ShopifyStore::$autoSubscribeInTests = true;

        return $store;
    }

    protected function createActiveStore(string $shop = 'active-store.myshopify.com'): ShopifyStore
    {
        ShopifyStore::$autoSubscribeInTests = false;
        $store = ShopifyStore::create([
            'shop' => $shop,
            'access_token' => 'shpat_test_active_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);
        ShopifyStore::$autoSubscribeInTests = true;

        $this->subscriptionService->activateSubscription($store, $this->proPlan);

        return $store;
    }

    protected function authStore(ShopifyStore $store): self
    {
        return $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => base64_encode($store->shop.'/admin'),
        ]);
    }

    // ==========================================
    // 1. Only One Subscription Plan
    // ==========================================

    public function test_1_only_one_socialnest_pro_plan_is_available(): void
    {
        $activePlans = Plan::where('is_active', true)->get();

        $this->assertCount(1, $activePlans);
        $this->assertEquals('SocialNest Pro', $activePlans->first()->name);
        $this->assertEquals('pro', $activePlans->first()->slug);
        $this->assertEquals(5.00, (float) $activePlans->first()->price);
    }

    // ==========================================
    // 2. Free Plan Cannot Be Activated
    // ==========================================

    public function test_2_free_plan_cannot_be_activated(): void
    {
        $store = $this->createInactiveStore();

        $this->expectException(InvalidArgumentException::class);
        $this->subscriptionService->activateSubscription($store, $this->freePlan);
    }

    public function test_2b_free_plan_submission_is_rejected(): void
    {
        $store = $this->createInactiveStore();

        $response = $this->authStore($store)->post('/shopify/plans/subscribe', [
            'plan_id' => $this->freePlan->id,
        ]);

        $response->assertStatus(404);
        $this->assertFalse($this->subscriptionService->isActive($store));
    }

    // ==========================================
    // 3. No Subscription Redirects to Plans & Billing
    // ==========================================

    public function test_3_no_subscription_redirects_to_plans_and_billing(): void
    {
        $store = $this->createInactiveStore();
        $expectedHost = base64_encode($store->shop.'/admin');

        $response = $this->authStore($store)->get('/shopify/dashboard');

        $response->assertRedirect(route('shopify.plans.index', ['shop' => $store->shop, 'host' => $expectedHost]));
        $response->assertSessionHas('warning', 'Paid plan is required to use features on this app');
    }

    // ==========================================
    // 4. Dummy Checkout Displays Exactly $5
    // ==========================================

    public function test_4_dummy_checkout_displays_exactly_5_dollars(): void
    {
        $store = $this->createInactiveStore();

        $response = $this->authStore($store)->get("/shopify/plans/checkout/{$this->proPlan->id}");

        $response->assertStatus(200);
        $response->assertSee('SocialNest Pro');
        $response->assertSee('$5.00');
        $response->assertSee('Confirm 30-Day Subscription');
        $response->assertSee('Dummy Subscription Disclosure');

        // Trying to checkout free plan returns 404
        $freeCheckout = $this->authStore($store)->get("/shopify/plans/checkout/{$this->freePlan->id}");
        $freeCheckout->assertStatus(404);
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
    }

    // ==========================================
    // 6. Exact Expiry Blocks Access
    // ==========================================

    public function test_6_exact_expiry_blocks_access(): void
    {
        $startTime = Carbon::parse('2026-11-01 12:00:00');
        Carbon::setTestNow($startTime);

        $store = $this->createInactiveStore();
        $sub = $this->subscriptionService->activateSubscription($store, $this->proPlan);

        // At exact expiry timestamp (current_period_end), access is strictly denied
        Carbon::setTestNow($sub->current_period_end);

        $this->assertFalse($this->subscriptionService->isActive($store));
    }

    // ==========================================
    // 7. Existing Free Subscription Does Not Grant Access
    // ==========================================

    public function test_7_existing_free_subscription_does_not_grant_access(): void
    {
        $store = $this->createInactiveStore();

        // Simulate legacy active subscription on free plan
        $legacySub = ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $this->freePlan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'activated_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(30),
        ]);

        $this->assertFalse($this->subscriptionService->isActive($store));

        // Status is automatically transitioned to expired
        $legacySub->refresh();
        $this->assertEquals(ShopSubscription::STATUS_EXPIRED, $legacySub->status);
    }

    // ==========================================
    // 8. Active Subscription Grants Access
    // ==========================================

    public function test_8_active_subscription_grants_access(): void
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
    // 9. Active Subscription Can Be Cancelled
    // ==========================================

    public function test_9_active_subscription_can_be_cancelled(): void
    {
        $store = $this->createActiveStore();

        $response = $this->authStore($store)->post('/shopify/plans/cancel');

        $response->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));

        $sub = $store->subscriptions()->latest('id')->first();
        $this->assertNotNull($sub);
        $this->assertEquals(ShopSubscription::STATUS_CANCELLED, $sub->status);
        $this->assertNotNull($sub->cancelled_at);
        $this->assertFalse($this->subscriptionService->isActive($store));
    }

    // ==========================================
    // 10. Cancellation Requires Confirmation
    // ==========================================

    public function test_10_cancellation_modal_is_present_in_view(): void
    {
        $store = $this->createActiveStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Cancel Subscription Immediately?');
        $response->assertSee('Keep Subscription');
        $response->assertSee('Cancel &amp; Block Access Instantly', false);
    }

    // ==========================================
    // 11. Cancellation Immediately Revokes Access
    // ==========================================

    public function test_11_cancellation_immediately_revokes_access(): void
    {
        $store = $this->createActiveStore();
        $this->subscriptionService->cancelSubscription($store);

        // Immediately inactive!
        $this->assertFalse($this->subscriptionService->isActive($store));

        $dash = $this->authStore($store)->get('/shopify/dashboard');
        $dash->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));
        $dash->assertSessionHas('warning', 'Paid plan is required to use features on this app');
    }

    // ==========================================
    // 12. Cancelled Subscription Disables Storefront Immediately
    // ==========================================

    public function test_12_cancelled_subscription_disables_storefront_immediately(): void
    {
        $store = $this->createActiveStore();
        $store->getStorefrontSettings()->update(['is_enabled' => true]);

        $this->subscriptionService->cancelSubscription($store);

        $configResponse = $this->getJson("/api/storefront/config?shop={$store->shop}");
        $configResponse->assertStatus(200);
        $configResponse->assertJson([
            'success' => true,
            'enabled' => false,
            'subscription_active' => false,
            'links' => [],
        ]);

        $trackResponse = $this->postJson('/api/storefront/track-click', [
            'shop' => $store->shop,
            'platform' => 'whatsapp',
        ]);
        $trackResponse->assertStatus(403);
    }

    // ==========================================
    // 13. Cancelled Subscription Cannot Access Protected APIs
    // ==========================================

    public function test_13_cancelled_subscription_cannot_access_protected_apis(): void
    {
        $store = $this->createActiveStore();
        $this->subscriptionService->cancelSubscription($store);

        $response = $this->authStore($store)->json('POST', '/shopify/social-links/reorder', [
            'order' => [1, 2, 3],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'SUBSCRIPTION_REQUIRED',
            'code' => 'SUBSCRIPTION_REQUIRED',
        ]);
    }

    // ==========================================
    // 14. Re-Subscribe After Cancellation Restores Access
    // ==========================================

    public function test_14_resubscribe_after_cancellation_restores_access(): void
    {
        $store = $this->createActiveStore();
        $this->subscriptionService->cancelSubscription($store);
        $this->assertFalse($this->subscriptionService->isActive($store));

        $newSub = $this->subscriptionService->activateSubscription($store, $this->proPlan);
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $newSub->status);
        $this->assertTrue($this->subscriptionService->isActive($store));

        $dash = $this->authStore($store)->get('/shopify/dashboard');
        $dash->assertStatus(200);
    }

    // ==========================================
    // 15. Cross-Shop Cancellation Is Rejected
    // ==========================================

    public function test_15_cross_shop_cancellation_is_rejected(): void
    {
        $storeA = $this->createActiveStore('store-a.myshopify.com');
        $storeB = $this->createActiveStore('store-b.myshopify.com');

        // Store A attempts to cancel store B by passing parameters
        $this->authStore($storeA)->post('/shopify/plans/cancel', [
            'shop_id' => $storeB->id,
            'shop' => $storeB->shop,
        ]);

        $subA = $storeA->subscriptions()->latest('id')->first();
        $subB = $storeB->subscriptions()->latest('id')->first();

        // Only store A's subscription was cancelled; store B remains untouched and active
        $this->assertEquals(ShopSubscription::STATUS_CANCELLED, $subA->status);
        $this->assertEquals(ShopSubscription::STATUS_ACTIVE, $subB->status);
        $this->assertTrue($this->subscriptionService->isActive($storeB));
    }

    // ==========================================
    // 16. Inactive Subscription Cannot Access Protected APIs
    // ==========================================

    public function test_16_inactive_subscription_cannot_access_protected_apis(): void
    {
        $store = $this->createInactiveStore();

        $response = $this->authStore($store)->json('POST', '/shopify/social-links/reorder', [
            'order' => [1, 2, 3],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'SUBSCRIPTION_REQUIRED',
            'code' => 'SUBSCRIPTION_REQUIRED',
        ]);
    }

    // ==========================================
    // 17. Active Storefront Remains Functional
    // ==========================================

    public function test_17_active_storefront_remains_functional(): void
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
    // 18. Expired Storefront Widgets Are Disabled
    // ==========================================

    public function test_18_expired_storefront_widgets_are_disabled(): void
    {
        $startTime = Carbon::parse('2026-11-01 10:00:00');
        Carbon::setTestNow($startTime);

        $store = $this->createActiveStore();

        // Move past 30 days
        Carbon::setTestNow($startTime->copy()->addDays(31));

        $configResponse = $this->getJson("/api/storefront/config?shop={$store->shop}");
        $configResponse->assertStatus(200);
        $configResponse->assertJson([
            'success' => true,
            'enabled' => false,
            'subscription_active' => false,
            'links' => [],
        ]);

        $trackResponse = $this->postJson('/api/storefront/track-click', [
            'shop' => $store->shop,
            'platform' => 'whatsapp',
        ]);
        $trackResponse->assertStatus(403);
    }

    // ==========================================
    // 19. Shopify OAuth and Webhooks Continue Working
    // ==========================================

    public function test_19_shopify_oauth_and_uninstall_webhooks_continue_working(): void
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
    // 20. Resume Endpoint Informs Merchant That Cancellation Is Instant
    // ==========================================

    public function test_20_resume_subscription_informs_merchant_cancellation_is_instant(): void
    {
        $store = $this->createActiveStore();

        $this->subscriptionService->cancelSubscription($store);

        $response = $this->authStore($store)->post('/shopify/plans/resume');
        $response->assertRedirect(route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => base64_encode($store->shop.'/admin'),
        ]));
        $response->assertSessionHas('warning', 'Subscriptions are cancelled immediately. Please re-subscribe to activate a new 30-day period.');
    }
}
