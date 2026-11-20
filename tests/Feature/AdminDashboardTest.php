<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'name' => 'Dashboard Admin',
            'email' => 'admin@socialnest.test',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_can_view_dashboard_with_analytics_section(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        // Existing cards & headers
        $response->assertSee('Welcome back, Dashboard Admin');
        $response->assertSee('Total Merchants');
        $response->assertSee('Active Merchants');
        $response->assertSee('Active Subscriptions');
        $response->assertSee('Social Links Created');
        $response->assertSee('Recent Merchants');

        // New Analytics Section
        $response->assertSee('Merchant Registration Trend');
        $response->assertSee('Subscription Status Distribution');
        $response->assertSee('7 Days');
        $response->assertSee('30 Days');
        $response->assertSee('90 Days');
        $response->assertSee('registrationTrendChart');
    }

    public function test_dashboard_handles_empty_database_gracefully(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalStores', 0);
        $response->assertViewHas('activeStores', 0);
        $response->assertViewHas('totalSubscriptions', 0);
        $response->assertViewHas('subscriptionDistribution', []);

        $trends = $response->viewData('registrationTrends');
        $this->assertIsArray($trends);
        $this->assertArrayHasKey(7, $trends);
        $this->assertArrayHasKey(30, $trends);
        $this->assertArrayHasKey(90, $trends);
        $this->assertSame(0, $trends[7]['total']);
        $this->assertSame(0, $trends[30]['total']);
        $this->assertSame(0, $trends[90]['total']);

        // Empty state message for subscriptions
        $response->assertSee('No subscriptions recorded');
    }

    public function test_merchant_registration_trends_aggregate_real_database_records(): void
    {
        // 2 stores created today (within 7, 30, and 90 days)
        ShopifyStore::factory()->count(2)->create([
            'created_at' => now(),
        ]);

        // 3 stores created 15 days ago (within 30 and 90 days, NOT in 7 days)
        ShopifyStore::factory()->count(3)->create([
            'created_at' => now()->subDays(15),
        ]);

        // 4 stores created 60 days ago (within 90 days, NOT in 7 or 30 days)
        ShopifyStore::factory()->count(4)->create([
            'created_at' => now()->subDays(60),
        ]);

        // 1 store created 120 days ago (outside 90 days window)
        ShopifyStore::factory()->create([
            'created_at' => now()->subDays(120),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $trends = $response->viewData('registrationTrends');

        $this->assertSame(2, $trends[7]['total']);
        $this->assertSame(5, $trends[30]['total']); // 2 + 3
        $this->assertSame(9, $trends[90]['total']); // 2 + 3 + 4
        $this->assertCount(7, $trends[7]['labels']);
        $this->assertCount(30, $trends[30]['labels']);
        $this->assertCount(90, $trends[90]['labels']);
    }

    public function test_subscription_status_distribution_aggregates_real_database_records(): void
    {
        ShopifyStore::$autoSubscribeInTests = false;

        $plan = Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'SocialNest Pro',
                'price' => 5.00,
                'billing_interval' => '30_days',
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        $store1 = ShopifyStore::factory()->create();
        $store2 = ShopifyStore::factory()->create();
        $store3 = ShopifyStore::factory()->create();

        // 1 Active subscription
        ShopSubscription::create([
            'shop_id' => $store1->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'activated_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(30),
        ]);

        // 1 Cancelled subscription
        ShopSubscription::create([
            'shop_id' => $store2->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
            'activated_at' => now()->subDays(20),
            'current_period_start' => now()->subDays(20),
            'current_period_end' => now()->subDays(5),
            'cancelled_at' => now()->subDays(5),
        ]);

        // 1 Expired subscription
        ShopSubscription::create([
            'shop_id' => $store3->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
            'activated_at' => now()->subDays(40),
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        // Reset flag
        ShopifyStore::$autoSubscribeInTests = true;

        $response->assertStatus(200);
        $response->assertViewHas('totalSubscriptions', 3);

        $distribution = collect($response->viewData('subscriptionDistribution'));
        $this->assertCount(3, $distribution);

        $active = $distribution->firstWhere('status', 'active');
        $this->assertNotNull($active);
        $this->assertSame(1, $active['count']);
        $this->assertEquals(33.3, $active['percentage']);

        $cancelled = $distribution->firstWhere('status', 'cancelled');
        $this->assertNotNull($cancelled);
        $this->assertSame(1, $cancelled['count']);
        $this->assertEquals(33.3, $cancelled['percentage']);

        $expired = $distribution->firstWhere('status', 'expired');
        $this->assertNotNull($expired);
        $this->assertSame(1, $expired['count']);
        $this->assertEquals(33.3, $expired['percentage']);

        // Donut canvas and status labels rendered
        $response->assertSee('subscriptionDistributionChart');
        $response->assertSee('Active');
        $response->assertSee('Cancelled');
        $response->assertSee('Expired');
        $response->assertSee('3 Subscriptions');
    }

    public function test_dashboard_json_request_returns_analytics_datasets(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->json('GET', route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'registrationTrends' => [
                '7' => ['labels', 'data', 'total'],
                '30' => ['labels', 'data', 'total'],
                '90' => ['labels', 'data', 'total'],
            ],
            'subscriptionDistribution',
            'totalSubscriptions',
        ]);
    }
}
