<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DashboardLiveSubscriptionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ShopifyStore::$autoSubscribeInTests = false;

        Config::set('shopify.api_key', 'test_key');
        Config::set('shopify.api_secret', 'test_secret');
        Config::set('shopify.app_url', 'https://socialnest.test');
    }

    protected function tearDown(): void
    {
        ShopifyStore::$autoSubscribeInTests = true;
        parent::tearDown();
    }

    protected function createStore(string $shop = 'teststore-vsbhaml7.myshopify.com'): ShopifyStore
    {
        return ShopifyStore::create([
            'shop' => $shop,
            'access_token' => 'shpat_test_access_token_123',
            'is_active' => true,
            'installed_at' => now(),
            'scope' => 'read_products,write_themes',
        ]);
    }

    public function test_dashboard_displays_dynamic_active_subscription_for_starter_plan(): void
    {
        $plan = Plan::where('slug', 'socialvire-starter')->first()
            ?? Plan::where('slug', 'starter')->first()
            ?? Plan::firstOrCreate(
                ['slug' => 'socialvire-starter'],
                [
                    'name' => 'Socialvire Starter',
                    'price' => 0.00,
                    'currency' => 'USD',
                    'billing_interval' => 'permanent',
                    'social_link_limit' => null,
                    'is_active' => true,
                ]
            );

        $store = $this->createStore();

        ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $plan->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/76036276588',
            'plan_handle' => 'socialvire-starter',
            'status' => ShopSubscription::STATUS_ACTIVE,
            'billing_interval' => 'PERMANENT',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'activated_at' => now(),
            'last_verified_at' => now(),
        ]);

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ]));

        $response->assertStatus(200);
        $response->assertSee($plan->name.' Active');
        $response->assertSee('Manage Billing');
        $response->assertDontSee('Upgrade to Pro');
    }

    public function test_dashboard_displays_dynamic_active_subscription_for_pro_plan(): void
    {
        $plan = Plan::where('slug', 'pro')->first()
            ?? Plan::firstOrCreate(
                ['slug' => 'pro'],
                [
                    'name' => 'SocialNest Pro',
                    'price' => 9.99,
                    'currency' => 'USD',
                    'billing_interval' => '30_days',
                    'social_link_limit' => null,
                    'is_active' => true,
                ]
            );

        $store = $this->createStore();

        ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $plan->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/99999999999',
            'plan_handle' => 'pro',
            'status' => ShopSubscription::STATUS_ACTIVE,
            'billing_interval' => '30_DAYS',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'activated_at' => now(),
            'last_verified_at' => now(),
        ]);

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ]));

        $response->assertStatus(200);
        $response->assertSee('SocialNest Pro Active');
        $response->assertSee('Manage Billing');
        $response->assertDontSee('Upgrade to Pro');
    }

    public function test_dashboard_displays_free_plan_and_upgrade_cta_when_no_active_subscription(): void
    {
        $freePlan = Plan::where('slug', 'free')->first()
            ?? Plan::firstOrCreate(
                ['slug' => 'free'],
                [
                    'name' => 'Free Plan',
                    'price' => 0.00,
                    'currency' => 'USD',
                    'billing_interval' => 'permanent',
                    'social_link_limit' => 2,
                    'is_active' => true,
                ]
            );

        $store = $this->createStore();

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ]));

        $response->assertStatus(200);
        $response->assertSee($freePlan->name);
        $response->assertSee('Upgrade to Pro');
    }

    public function test_purchase_success_toast_is_displayed_once_and_cleared_on_refresh(): void
    {
        $plan = Plan::firstOrCreate(
            ['slug' => 'socialvire-starter'],
            [
                'name' => 'Socialvire Starter',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'social_link_limit' => null,
                'is_active' => true,
            ]
        );

        $store = $this->createStore();

        ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $plan->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/76036276588',
            'plan_handle' => 'socialvire-starter',
            'status' => ShopSubscription::STATUS_ACTIVE,
            'billing_interval' => 'PERMANENT',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'activated_at' => now(),
            'last_verified_at' => now(),
        ]);

        // 1. First request with flashed purchase_success message
        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
            '_flash' => [
                'new' => [],
                'old' => ['purchase_success'],
            ],
            'purchase_success' => 'Your Socialvire Starter plan is now active. Enjoy your premium features!',
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Purchase Successful!');
        $response->assertSee('Your Socialvire Starter plan is now active. Enjoy your premium features!');

        // 2. Subsequent request (refresh) where session flash has expired
        $refreshResponse = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ]));

        $refreshResponse->assertStatus(200);
        $refreshResponse->assertDontSee('Purchase Successful!');
    }

    public function test_failed_verification_does_not_display_purchase_success_toast(): void
    {
        $store = $this->createStore();

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
            'warning' => 'Subscription could not be verified by Shopify.',
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => 'dGVzdHN0b3JlLXZzYmhhbWw3Lm15c2hvcGlmeS5jb20vYWRtaW4',
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('Purchase Successful!');
        $response->assertSee('Subscription could not be verified by Shopify.');
    }
}
