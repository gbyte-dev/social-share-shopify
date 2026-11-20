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
use Tests\TestCase;

class PlansViewRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $freePlan;

    protected Plan $proPlan;

    protected SubscriptionService $subscriptionService;

    protected EntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();

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
        Carbon::setTestNow();
        ShopifyStore::$autoSubscribeInTests = true;
        parent::tearDown();
    }

    protected function createStore(string $shop = 'merchant.myshopify.com'): ShopifyStore
    {
        ShopifyStore::$autoSubscribeInTests = false;

        try {
            return ShopifyStore::create([
                'shop' => $shop,
                'access_token' => 'shpat_test_token_123',
                'scope' => 'read_products,write_themes',
                'is_active' => true,
                'installed_at' => now(),
            ]);
        } finally {
            ShopifyStore::$autoSubscribeInTests = true;
        }
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

    /**
     * Verify the Plans page renders for an authenticated merchant.
     */
    public function test_plans_page_renders_for_authenticated_merchant(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.plans.index');
    }

    /**
     * Verify the page header displays the exact required heading, subtitle, and Free Forever message.
     */
    public function test_page_header_displays_required_heading_and_subtitle_and_free_forever(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Choose Your Plan');
        $response->assertSee('Get full access to everything SocialNest offers.');
        $response->assertSee('Free Forever');
    }

    /**
     * Verify single plan card: SocialNest Free, price $0 / Forever.
     */
    public function test_displays_single_plan_card_socialnest_free_at_zero_dollars_forever(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('SocialNest Free');
        $response->assertSee('$');
        $response->assertSee('0');
        $response->assertSee('/ Forever');
    }

    /**
     * Verify all 8 required benefits are clearly listed on the plan card.
     */
    public function test_displays_all_eight_required_benefit_items(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Unlimited Social Links');
        $response->assertSee('Floating Social Dock');
        $response->assertSee('Storefront Customization');
        $response->assertSee('Product Sharing');
        $response->assertSee('Wishlist');
        $response->assertSee('Analytics & Click Tracking');
        $response->assertSee('Complete Dashboard Access');
        $response->assertSee('All Available SocialNest Features');
    }

    /**
     * Verify CTA button has label 'Claim Free Plan'.
     */
    public function test_cta_button_has_label_claim_free_plan(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Claim Free Plan');
        $response->assertSee('claimPlanBtn');
    }

    /**
     * Verify all reference features from the Shopify pricing specification are displayed.
     */
    public function test_displays_shopify_reference_plan_features(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Core floating social sharing dock');
        $response->assertSee('WhatsApp product sharing');
        $response->assertSee('Standard dock positioning');
        $response->assertSee('Shopify Theme App Extension');
        $response->assertSee('Add Wishlist feature on store');
        $response->assertSee('All features free for now');
    }

    /**
     * Verify the two-column desktop structure and right-side information panel exist.
     */
    public function test_displays_two_column_desktop_structure_and_info_panel(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('sn-plans-grid');
        $response->assertSee('sn-card-main');
        $response->assertSee('sn-card-side');
        $response->assertSee('Everything included with SocialNest Free');
        $response->assertSee('Free Forever Plan Guarantee');
    }

    /**
     * Verify the three additional information cards below the plan grid.
     */
    public function test_displays_three_additional_information_cards(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('100% Free Forever');
        $response->assertSee('Complete Storefront Customization');
        $response->assertSee('Live Click Analytics');
    }

    /**
     * Verify no other plans appear (Pro, Growth, Starter, Scale, etc.).
     */
    public function test_no_other_plans_appear_on_page(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertDontSee('Growth');
        $response->assertDontSee('Socialvire Starter');
        $response->assertDontSee('Scale');
        $response->assertDontSee('SocialNest Pro ($');
        $response->assertDontSee('Upgrade to Pro ($5.00)');
    }

    /**
     * Verify dummy subscription checkout or payment UI is not displayed.
     */
    public function test_dummy_subscription_checkout_and_payment_ui_not_displayed(): void
    {
        $store = $this->createStore();

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertDontSee('Confirm Dummy Subscription');
        $response->assertDontSee('Dummy billing');
        $response->assertDontSee('Dummy subscription disclosure');
    }

    /**
     * Verify opening the plans page does not activate a Pro subscription or create redundant subscriptions.
     */
    public function test_opening_page_does_not_activate_subscription_when_unsubscribed(): void
    {
        $store = $this->createStore();
        $this->subscriptionService->assignFreeSubscription($store);

        $initialSubscription = ShopSubscription::where('shop_id', $store->id)->latest('id')->first();

        $response = $this->authStore($store)->get('/shopify/plans');
        $response->assertStatus(200);

        $latestSubscription = ShopSubscription::where('shop_id', $store->id)->latest('id')->first();

        $this->assertSame($initialSubscription->id, $latestSubscription->id);
        $this->assertFalse($this->entitlementService->isPro($store));
    }

    /**
     * Verify subscription status is preserved and cancellation modal remains functional.
     */
    public function test_cancellation_modal_and_active_status_preserved_for_active_store(): void
    {
        $store = $this->createStore();
        $this->subscriptionService->activateSubscription($store, $this->proPlan);

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Active Subscription');
        $response->assertSee('Cancel your subscription?');
        $response->assertSee('Keep subscription');
        $response->assertSee('Confirm cancellation');
    }

    /**
     * Verify cancelled subscription status is accurately displayed without falsely reporting active free.
     */
    public function test_cancelled_status_displayed_truthfully(): void
    {
        $store = $this->createStore();
        $this->partialMock(SubscriptionService::class, function ($mock) use ($store): void {
            $cancelledSub = new ShopSubscription([
                'shop_id' => $store->id,
                'status' => 'cancelled',
                'activated_at' => now()->subDays(10),
            ]);
            $mock->shouldReceive('getActiveSubscription')->andReturnNull();
            $mock->shouldReceive('getLatestSubscription')->andReturn($cancelledSub);
        });

        $response = $this->authStore($store)->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertSee('Subscription Cancelled');
        $response->assertDontSee('Active Subscription');
    }
}
