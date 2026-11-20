<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMerchantDataTableTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'name' => 'DataTable Admin',
            'email' => 'admin@socialnest.test',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_merchants_page(): void
    {
        $response = $this->get(route('admin.merchants.index'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_empty_database_renders_dedicated_empty_state_card_and_no_datatable_markup(): void
    {
        // Ensure no merchants exist
        $this->assertSame(0, ShopifyStore::count());

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.merchants.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.merchants.index');

        // Case A Empty Card must be present
        $response->assertSee('No merchants have installed Socialvire yet');
        $response->assertSee('When a Shopify merchant installs your app, their store will automatically appear here.');
        $response->assertSee('0 Total Merchants');

        // DataTable markup, headers, and JS must NOT be rendered
        $response->assertDontSee('id="merchants-table"', false);
        $response->assertDontSee('<th>STORE</th>', false);
        $response->assertDontSee('<th>PLAN</th>', false);
        $response->assertDontSee('id="merchants-filter-form"', false);
        $response->assertDontSee('jquery.dataTables.min.js', false);
    }

    public function test_existing_merchants_renders_datatable_markup_and_scripts(): void
    {
        ShopifyStore::factory()->create([
            'shop' => 'active-shop.myshopify.com',
            'is_active' => true,
            'uninstalled_at' => null,
        ]);

        $this->assertGreaterThan(0, ShopifyStore::count());

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.merchants.index'));

        $response->assertStatus(200);

        // Case A Empty card must NOT be present
        $response->assertDontSee('No merchants have installed Socialvire yet');

        // DataTable structure must be rendered
        $response->assertSee('id="merchants-table"', false);
        $response->assertSee('<th>STORE</th>', false);
        $response->assertSee('<th>PLAN</th>', false);
        $response->assertSee('<th>ACCOUNT STATUS</th>', false);
        $response->assertSee('<th>SUBSCRIPTION</th>', false);
        $response->assertSee('<th>INSTALLED ON</th>', false);
        $response->assertSee('<th>ACTION</th>', false);

        // Redundant top filter card is removed; DataTables scripts and counter must be present
        $response->assertDontSee('filter-card', false);
        $response->assertDontSee('id="merchants-filter-form"', false);
        $response->assertSee('jquery.dataTables.min.js', false);
        $response->assertSee('1 Total Merchants');
    }

    public function test_ajax_request_returns_valid_datatables_json_payload(): void
    {
        ShopifyStore::$autoSubscribeInTests = false;

        $store = ShopifyStore::factory()->create([
            'shop' => 'gadgets-store.myshopify.com',
            'is_active' => true,
            'uninstalled_at' => null,
            'installed_at' => now()->subDays(5),
        ]);

        $plan = Plan::firstOrCreate(
            ['slug' => 'growth-pro'],
            [
                'name' => 'Growth Pro',
                'price' => 9.99,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'description' => 'Growth plan',
                'features' => ['All features'],
                'is_active' => true,
            ]
        );
        ShopSubscription::create([
            'shop_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'activated_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(30),
        ]);

        ShopifyStore::$autoSubscribeInTests = true;

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'store',
                    'plan',
                    'account_status',
                    'subscription',
                    'installed_on',
                    'action',
                ],
            ],
        ]);

        $json = $response->json();
        $this->assertSame(1, $json['draw']);
        $this->assertSame(1, $json['recordsTotal']);
        $this->assertSame(1, $json['recordsFiltered']);

        $row = $json['data'][0];
        $this->assertStringContainsString('gadgets-store.myshopify.com', $row['store']);
        $this->assertStringContainsString("Merchant ID: #{$store->id}", $row['store']);
        $this->assertSame('Growth Pro', $row['plan']);
        $this->assertStringContainsString('status-active', $row['account_status']);
        $this->assertStringContainsString('Active', $row['account_status']);
        $this->assertStringContainsString('Active', $row['subscription']);
        $this->assertStringContainsString(route('admin.merchants.show', $store), $row['action']);
        $this->assertStringContainsString('View Details', $row['action']);
    }

    public function test_ajax_search_filters_merchants_by_shop_domain(): void
    {
        ShopifyStore::factory()->create(['shop' => 'apple-store.myshopify.com']);
        ShopifyStore::factory()->create(['shop' => 'banana-store.myshopify.com']);
        ShopifyStore::factory()->create(['shop' => 'cherry-store.myshopify.com']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'search' => ['value' => 'banana'],
            ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame(3, $json['recordsTotal']);
        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertCount(1, $json['data']);
        $this->assertStringContainsString('banana-store.myshopify.com', $json['data'][0]['store']);
    }

    public function test_ajax_search_with_no_matches_returns_zero_filtered_records(): void
    {
        ShopifyStore::factory()->create(['shop' => 'active-shop.myshopify.com']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'search' => ['value' => 'non-existent-store-query'],
            ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame(1, $json['recordsTotal']);
        $this->assertSame(0, $json['recordsFiltered']);
        $this->assertEmpty($json['data']);
    }

    public function test_ajax_filter_by_account_status(): void
    {
        // Active merchant
        ShopifyStore::factory()->create([
            'shop' => 'active-merchant.myshopify.com',
            'is_active' => true,
            'uninstalled_at' => null,
        ]);

        // Uninstalled merchant
        ShopifyStore::factory()->create([
            'shop' => 'uninstalled-merchant.myshopify.com',
            'is_active' => false,
            'uninstalled_at' => now()->subDay(),
        ]);

        // 1. Filter active
        $responseActive = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'status' => 'active',
            ]));

        $responseActive->assertStatus(200);
        $jsonActive = $responseActive->json();
        $this->assertSame(2, $jsonActive['recordsTotal']);
        $this->assertSame(1, $jsonActive['recordsFiltered']);
        $this->assertStringContainsString('active-merchant.myshopify.com', $jsonActive['data'][0]['store']);

        // 2. Filter uninstalled
        $responseUninstalled = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'status' => 'uninstalled',
            ]));

        $responseUninstalled->assertStatus(200);
        $jsonUninstalled = $responseUninstalled->json();
        $this->assertSame(2, $jsonUninstalled['recordsTotal']);
        $this->assertSame(1, $jsonUninstalled['recordsFiltered']);
        $this->assertStringContainsString('uninstalled-merchant.myshopify.com', $jsonUninstalled['data'][0]['store']);
    }

    public function test_ajax_pagination_honors_start_and_length(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            ShopifyStore::factory()->create([
                'shop' => sprintf('store-%02d.myshopify.com', $i),
            ]);
        }

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'start' => 10,
                'length' => 10,
            ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame(15, $json['recordsTotal']);
        $this->assertSame(15, $json['recordsFiltered']);
        $this->assertCount(5, $json['data']);
    }

    public function test_ajax_sorting_by_shop_domain(): void
    {
        ShopifyStore::factory()->create(['shop' => 'zeta-store.myshopify.com']);
        ShopifyStore::factory()->create(['shop' => 'alpha-store.myshopify.com']);
        ShopifyStore::factory()->create(['shop' => 'mid-store.myshopify.com']);

        // Column 0 is shop, order asc
        $responseAsc = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'order' => [
                    ['column' => 0, 'dir' => 'asc'],
                ],
            ]));

        $responseAsc->assertStatus(200);
        $dataAsc = $responseAsc->json('data');
        $this->assertStringContainsString('alpha-store.myshopify.com', $dataAsc[0]['store']);
        $this->assertStringContainsString('mid-store.myshopify.com', $dataAsc[1]['store']);
        $this->assertStringContainsString('zeta-store.myshopify.com', $dataAsc[2]['store']);

        // Column 0 is shop, order desc
        $responseDesc = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
                'order' => [
                    ['column' => 0, 'dir' => 'desc'],
                ],
            ]));

        $responseDesc->assertStatus(200);
        $dataDesc = $responseDesc->json('data');
        $this->assertStringContainsString('zeta-store.myshopify.com', $dataDesc[0]['store']);
        $this->assertStringContainsString('mid-store.myshopify.com', $dataDesc[1]['store']);
        $this->assertStringContainsString('alpha-store.myshopify.com', $dataDesc[2]['store']);
    }

    public function test_merchant_without_subscription_displays_no_active_plan_and_no_active_subscription(): void
    {
        ShopifyStore::$autoSubscribeInTests = false;

        $store = ShopifyStore::factory()->create([
            'shop' => 'nosub-store.myshopify.com',
            'is_active' => false,
            'uninstalled_at' => null,
        ]);

        ShopifyStore::$autoSubscribeInTests = true;

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.merchants.index', [
                'draw' => 1,
            ]));

        $response->assertStatus(200);
        $row = $response->json('data.0');

        $this->assertSame('No active plan', $row['plan']);
        $this->assertStringContainsString('No Active Subscription', $row['subscription']);
        $this->assertStringContainsString('status-inactive', $row['account_status']);
    }

    public function test_authenticated_admin_can_view_merchant_details_page(): void
    {
        $store = ShopifyStore::factory()->create([
            'shop' => 'details-store.myshopify.com',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.merchants.show', $store));

        $response->assertStatus(200);
        $response->assertViewIs('admin.merchants.show');
        $response->assertSee('details-store.myshopify.com');
    }
}
