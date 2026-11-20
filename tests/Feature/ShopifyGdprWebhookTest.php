<?php

namespace Tests\Feature;

use App\Models\PendingShopRedaction;
use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Models\SocialLink;
use App\Models\SocialLinkClick;
use App\Models\StorefrontSetting;
use App\Models\WishlistItem;
use App\Services\ShopRedactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ShopifyGdprWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'test_webhook_secret_key_12345';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shopify.webhook_secret', $this->secret);
        Config::set('shopify.api_secret', $this->secret);
        Storage::fake('public');
        Storage::fake('local');
    }

    /**
     * Send a signed webhook POST request to /shopify/webhooks.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function postWebhook(string $topic, string $shop, array $payload, ?string $hmacOverride = null): TestResponse
    {
        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $hmac = $hmacOverride ?? base64_encode(hash_hmac('sha256', $rawPayload, $this->secret, true));

        $headers = [
            'X-Shopify-Topic' => $topic,
            'X-Shopify-Shop-Domain' => $shop,
            'X-Shopify-Hmac-Sha256' => $hmac,
            'Content-Type' => 'application/json',
        ];

        return $this->call(
            'POST',
            '/shopify/webhooks',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $rawPayload
        );
    }

    public function test_1_invalid_hmac_is_rejected_with_401(): void
    {
        $response = $this->postWebhook(
            'customers/data_request',
            'test-store.myshopify.com',
            ['customer' => ['id' => 123]],
            'invalid_tampered_hmac_signature'
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid HMAC signature']);
    }

    public function test_2_valid_customers_data_request_returns_200_and_compiles_data(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'alpha.myshopify.com']);

        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_101',
            'product_id' => 'prod_1',
            'product_title' => 'Sample Shirt',
            'product_handle' => 'sample-shirt',
            'product_price' => '25.00',
            'currency' => 'USD',
        ]);

        $payload = [
            'shop_id' => 9991,
            'shop_domain' => 'alpha.myshopify.com',
            'customer' => [
                'id' => 'cust_101',
                'email' => 'customer@example.com',
            ],
            'orders_requested' => [],
        ];

        $response = $this->postWebhook('customers/data_request', 'alpha.myshopify.com', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Customer data retrieved and export created',
            'data' => [
                'shop' => 'alpha.myshopify.com',
                'customer_id' => 'cust_101',
                'record_count' => 1,
            ],
        ]);
    }

    public function test_3_customer_data_export_includes_all_applicable_stored_customer_data(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'alpha.myshopify.com']);

        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_202',
            'product_id' => 'prod_alpha_1',
            'product_title' => 'Product Alpha',
            'product_handle' => 'product-alpha',
            'product_price' => '40.00',
            'currency' => 'USD',
        ]);

        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_202',
            'product_id' => 'prod_alpha_2',
            'product_title' => 'Product Beta',
            'product_handle' => 'product-beta',
            'product_price' => '60.00',
            'currency' => 'USD',
        ]);

        $payload = [
            'shop_domain' => 'alpha.myshopify.com',
            'customer' => ['id' => 'cust_202'],
        ];

        $response = $this->postWebhook('customers/data_request', 'alpha.myshopify.com', $payload);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, $data['record_count']);
        $this->assertCount(2, $data['wishlist_items']);
        $this->assertEquals('Product Alpha', $data['wishlist_items'][0]['title']);
        $this->assertEquals('Product Beta', $data['wishlist_items'][1]['title']);

        // Verify local export file was written
        $exportPath = "gdpr/exports/{$store->id}/customer_cust_202.json";
        $this->assertTrue(Storage::disk('local')->exists($exportPath));
    }

    public function test_4_customers_redact_deletes_only_correct_customer_data(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'alpha.myshopify.com']);

        // Customer A items
        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_target',
            'product_id' => 'prod_1',
            'product_title' => 'Target Customer Item 1',
        ]);
        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_target',
            'product_id' => 'prod_2',
            'product_title' => 'Target Customer Item 2',
        ]);

        // Customer B items
        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_other',
            'product_id' => 'prod_3',
            'product_title' => 'Other Customer Item',
        ]);

        $payload = [
            'shop_domain' => 'alpha.myshopify.com',
            'customer' => ['id' => 'cust_target'],
        ];

        $response = $this->postWebhook('customers/redact', 'alpha.myshopify.com', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Target customer items must be deleted
        $this->assertEquals(0, WishlistItem::where('shop_id', $store->id)->where('customer_id', 'cust_target')->count());

        // Other customer items must remain untouched
        $this->assertEquals(1, WishlistItem::where('shop_id', $store->id)->where('customer_id', 'cust_other')->count());
    }

    public function test_5_guest_wishlist_remains_untouched_during_customer_redact(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'alpha.myshopify.com']);

        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_delete_me',
            'product_id' => 'prod_customer',
            'product_title' => 'Customer Product',
        ]);

        WishlistItem::create([
            'shop_id' => $store->id,
            'guest_uuid' => 'guest-uuid-1234567890abcdef',
            'product_id' => 'prod_guest',
            'product_title' => 'Guest Product',
        ]);

        $payload = [
            'shop_domain' => 'alpha.myshopify.com',
            'customer' => ['id' => 'cust_delete_me'],
        ];

        $response = $this->postWebhook('customers/redact', 'alpha.myshopify.com', $payload);

        $response->assertStatus(200);

        // Customer item deleted
        $this->assertEquals(0, WishlistItem::where('shop_id', $store->id)->where('customer_id', 'cust_delete_me')->count());

        // Guest item must remain intact
        $this->assertEquals(1, WishlistItem::where('shop_id', $store->id)->where('guest_uuid', 'guest-uuid-1234567890abcdef')->count());
    }

    public function test_6_another_shops_records_remain_untouched_during_customer_redact(): void
    {
        $storeA = ShopifyStore::factory()->create(['shop' => 'alpha.myshopify.com']);
        $storeB = ShopifyStore::factory()->create(['shop' => 'beta.myshopify.com']);

        // Same customer ID on both stores
        WishlistItem::create([
            'shop_id' => $storeA->id,
            'customer_id' => 'cust_shared_id',
            'product_id' => 'prod_store_a',
            'product_title' => 'Store A Product',
        ]);

        WishlistItem::create([
            'shop_id' => $storeB->id,
            'customer_id' => 'cust_shared_id',
            'product_id' => 'prod_store_b',
            'product_title' => 'Store B Product',
        ]);

        // Redact Customer on Store A only
        $payload = [
            'shop_domain' => 'alpha.myshopify.com',
            'customer' => ['id' => 'cust_shared_id'],
        ];

        $response = $this->postWebhook('customers/redact', 'alpha.myshopify.com', $payload);

        $response->assertStatus(200);

        // Store A item deleted
        $this->assertEquals(0, WishlistItem::where('shop_id', $storeA->id)->where('customer_id', 'cust_shared_id')->count());

        // Store B item must remain intact (Strict Multi-tenant isolation)
        $this->assertEquals(1, WishlistItem::where('shop_id', $storeB->id)->where('customer_id', 'cust_shared_id')->count());
    }

    public function test_7_shop_redact_removes_all_applicable_shop_owned_database_data(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'store-to-erase.myshopify.com']);
        $storeId = $store->id;

        // Populate all shop-owned tables
        SocialLink::create([
            'shopify_store_id' => $storeId,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/shop',
            'label' => 'Instagram',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        StorefrontSetting::create([
            'shopify_store_id' => $storeId,
            'is_enabled' => true,
            'position' => 'bottom_right',
        ]);

        SocialLinkClick::create([
            'shopify_store_id' => $storeId,
            'platform' => 'instagram',
            'link_type' => SocialLinkClick::TYPE_FLOATING_DOCK,
            'clicked_at' => now(),
        ]);

        WishlistItem::create([
            'shop_id' => $storeId,
            'customer_id' => 'cust_1',
            'product_id' => 'prod_1',
        ]);

        $plan = Plan::firstOrCreate(
            ['slug' => 'pro'],
            ['name' => 'SocialNest Pro', 'price' => 5.00, 'currency' => 'USD', 'billing_interval' => '30_days', 'is_active' => true]
        );

        ShopSubscription::create([
            'shop_id' => $storeId,
            'plan_id' => $plan->id,
            'status' => ShopSubscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(30),
        ]);

        $payload = [
            'shop_id' => $storeId,
            'shop_domain' => 'store-to-erase.myshopify.com',
        ];

        $response = $this->postWebhook('shop/redact', 'store-to-erase.myshopify.com', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'message' => 'Shop data erased successfully']);

        // Assert all shop-owned records are erased
        $this->assertNull(ShopifyStore::find($storeId));
        $this->assertEquals(0, SocialLink::where('shopify_store_id', $storeId)->count());
        $this->assertEquals(0, StorefrontSetting::where('shopify_store_id', $storeId)->count());
        $this->assertEquals(0, SocialLinkClick::where('shopify_store_id', $storeId)->count());
        $this->assertEquals(0, WishlistItem::where('shop_id', $storeId)->count());
        $this->assertEquals(0, ShopSubscription::where('shop_id', $storeId)->count());
        $this->assertDatabaseMissing('pending_shop_redactions', ['shop_domain' => 'store-to-erase.myshopify.com']);
    }

    public function test_8_shop_owned_uploaded_files_are_removed(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'file-store.myshopify.com']);
        $storeId = $store->id;

        // Place mock files in Storage disk public and local
        Storage::disk('public')->put("logos/{$storeId}/icon.svg", '<svg></svg>');
        Storage::disk('local')->put("gdpr/exports/{$storeId}/customer_data.json", '{}');

        // Mock uploads directory in public_path
        $uploadsDir = public_path("uploads/{$storeId}");
        if (! File::isDirectory($uploadsDir)) {
            File::makeDirectory($uploadsDir, 0755, true, true);
        }
        File::put("{$uploadsDir}/custom_icon.png", 'fake_png_data');

        $this->assertTrue(Storage::disk('public')->exists("logos/{$storeId}/icon.svg"));
        $this->assertTrue(File::isDirectory($uploadsDir));

        $payload = [
            'shop_id' => $storeId,
            'shop_domain' => 'file-store.myshopify.com',
        ];

        $response = $this->postWebhook('shop/redact', 'file-store.myshopify.com', $payload);

        $response->assertStatus(200);

        // Verify storage directories were removed
        $this->assertFalse(Storage::disk('public')->exists("logos/{$storeId}"));
        $this->assertFalse(Storage::disk('local')->exists("gdpr/exports/{$storeId}"));
        $this->assertFalse(File::isDirectory($uploadsDir));
    }

    public function test_9_global_platform_data_remains_untouched(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'global-test.myshopify.com']);

        DB::table('global_custom_platforms')->insertOrIgnore([
            'name' => 'Kick Global',
            'website_url' => 'https://kick.com',
            'logo_url' => 'https://cdn.example.com/kick.svg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'shop_domain' => 'global-test.myshopify.com',
        ];

        $response = $this->postWebhook('shop/redact', 'global-test.myshopify.com', $payload);

        $response->assertStatus(200);

        // Global platforms directory must NOT be wiped
        $this->assertDatabaseHas('global_custom_platforms', [
            'name' => 'Kick Global',
        ]);
    }

    public function test_10_duplicate_redaction_requests_are_safe(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'repeat-store.myshopify.com']);

        $customerPayload = [
            'shop_domain' => 'repeat-store.myshopify.com',
            'customer' => ['id' => 'cust_repeat'],
        ];

        // First delivery
        $res1 = $this->postWebhook('customers/redact', 'repeat-store.myshopify.com', $customerPayload);
        $res1->assertStatus(200);

        // Repeated duplicate delivery must be idempotent
        $res2 = $this->postWebhook('customers/redact', 'repeat-store.myshopify.com', $customerPayload);
        $res2->assertStatus(200);

        $shopPayload = [
            'shop_domain' => 'repeat-store.myshopify.com',
        ];

        // First shop redact
        $shopRes1 = $this->postWebhook('shop/redact', 'repeat-store.myshopify.com', $shopPayload);
        $shopRes1->assertStatus(200);

        // Repeated duplicate shop redact when store is already erased
        $shopRes2 = $this->postWebhook('shop/redact', 'repeat-store.myshopify.com', $shopPayload);
        $shopRes2->assertStatus(200);
        $shopRes2->assertJson(['status' => 'success', 'message' => 'Store already erased or does not exist']);
    }

    public function test_11_malformed_payloads_cannot_trigger_unintended_deletion(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'safe-store.myshopify.com']);

        WishlistItem::create([
            'shop_id' => $store->id,
            'customer_id' => 'cust_safe',
            'product_id' => 'prod_safe',
        ]);

        // Malformed payload: missing customer id
        $malformedPayload = [
            'shop_domain' => 'safe-store.myshopify.com',
            'customer' => [],
        ];

        $response = $this->postWebhook('customers/redact', 'safe-store.myshopify.com', $malformedPayload);

        $response->assertStatus(400);

        // Record must NOT have been deleted
        $this->assertEquals(1, WishlistItem::where('shop_id', $store->id)->where('customer_id', 'cust_safe')->count());
    }

    public function test_12_app_uninstalled_is_distinct_from_shop_redact(): void
    {
        $store = ShopifyStore::factory()->create([
            'shop' => 'uninstall-test.myshopify.com',
            'is_active' => true,
        ]);

        SocialLink::create([
            'shopify_store_id' => $store->id,
            'platform' => 'tiktok',
            'url' => 'https://tiktok.com/@test',
            'label' => 'TikTok',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = [
            'domain' => 'uninstall-test.myshopify.com',
        ];

        // Send app/uninstalled
        $response = $this->postWebhook('app/uninstalled', 'uninstall-test.myshopify.com', $payload);

        $response->assertStatus(200);

        $store->refresh();
        $this->assertFalse($store->is_active);
        $this->assertNotNull($store->uninstalled_at);

        // Data MUST be preserved after app/uninstalled for re-installation
        $this->assertDatabaseHas('shopify_stores', ['id' => $store->id]);
        $this->assertEquals(1, SocialLink::where('shopify_store_id', $store->id)->count());
    }

    // =========================================================================
    // FIX 1 Tests — Secure Customer Data Export Retrieval
    // =========================================================================

    public function test_13_correct_merchant_can_download_their_shops_export(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'merchant-download.myshopify.com']);
        $customerId = 'cust_export_123';

        $exportData = [
            'shop' => $store->shop,
            'customer_id' => $customerId,
            'wishlist_items' => [['product_id' => 'p1', 'title' => 'Sample']],
            'record_count' => 1,
        ];

        Storage::disk('local')->put(
            "gdpr/exports/{$store->id}/customer_{$customerId}.json",
            json_encode($exportData, JSON_PRETTY_PRINT)
        );

        // Authenticated merchant session
        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ])->get("/shopify/gdpr/export/{$customerId}?shop={$store->shop}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertHeader('Content-Disposition', "attachment; filename=\"customer_{$customerId}_export.json\"");
        $this->assertEquals($exportData, json_decode($response->getContent(), true));
    }

    public function test_14_another_shop_cannot_download_foreign_export(): void
    {
        $storeA = ShopifyStore::factory()->create(['shop' => 'store-a.myshopify.com']);
        $storeB = ShopifyStore::factory()->create(['shop' => 'store-b.myshopify.com']);
        $customerId = 'cust_private_999';

        // Store A has the export
        Storage::disk('local')->put(
            "gdpr/exports/{$storeA->id}/customer_{$customerId}.json",
            json_encode(['shop' => $storeA->shop, 'customer_id' => $customerId])
        );

        // Store B tries to download it
        $response = $this->withSession([
            'shopify_shop' => $storeB->shop,
            'shopify_store_id' => $storeB->id,
        ])->get("/shopify/gdpr/export/{$customerId}?shop={$storeB->shop}");

        // Store B must get 404 because export does not exist in Store B's directory
        $response->assertStatus(404);
        $response->assertJson(['error' => 'Customer export not found']);
    }

    public function test_15_unauthenticated_request_is_denied(): void
    {
        $response = $this->get('/shopify/gdpr/export/cust_123');

        // Middleware redirects unauthenticated requests to install flow
        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install'));
    }

    public function test_16_missing_export_returns_404(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'store-empty.myshopify.com']);

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ])->get("/shopify/gdpr/export/cust_not_found?shop={$store->shop}");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Customer export not found']);
    }

    public function test_17_path_traversal_is_rejected(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'store-secure.myshopify.com']);

        // Path traversal payload with invalid characters like dots or directory traversal attempts
        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ])->get("/shopify/gdpr/export/customer..dot_traversal?shop={$store->shop}");

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid customer ID format']);
    }

    // =========================================================================
    // FIX 2 Tests — Reliable Shop Data Erasure and Database-Backed Cleanup Retries
    // =========================================================================

    public function test_18_database_erasure_completes_and_creates_pending_record_when_filesystem_fails(): void
    {
        $store = ShopifyStore::factory()->create(['shop' => 'fail-fs.myshopify.com']);
        $storeId = $store->id;

        SocialLink::create([
            'shopify_store_id' => $storeId,
            'platform' => 'x',
            'url' => 'https://x.com/test',
            'label' => 'X',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put("logos/{$storeId}/test.svg", '<svg></svg>');

        // Mock ShopRedactionService to simulate filesystem failure
        $mock = $this->mock(ShopRedactionService::class);
        $mock->shouldReceive('purgeShopFilesystem')->with($storeId)->once()->andReturn(false);

        $payload = [
            'shop_id' => $storeId,
            'shop_domain' => 'fail-fs.myshopify.com',
        ];

        // Deliver shop/redact
        $response = $this->postWebhook('shop/redact', 'fail-fs.myshopify.com', $payload);

        // Controller must return 500 so Shopify knows cleanup is pending
        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'partial_failure',
            'message' => 'Database erased; filesystem cleanup pending retry',
        ]);

        // Database store record MUST be completely erased
        $this->assertNull(ShopifyStore::find($storeId));
        $this->assertEquals(0, SocialLink::where('shopify_store_id', $storeId)->count());

        // Persistent database tracking record MUST exist with attempts incremented
        $this->assertDatabaseHas('pending_shop_redactions', [
            'shop_domain' => 'fail-fs.myshopify.com',
            'store_id' => $storeId,
            'attempts' => 1,
        ]);
    }

    public function test_19_failed_filesystem_cleanup_can_be_retried_after_store_deleted(): void
    {
        $shopDomain = 'retry-store.myshopify.com';
        $storeId = 777;

        // Simulate state after database erasure: store is deleted, but files linger and tracking record is in database
        Storage::disk('public')->put("logos/{$storeId}/lingering_logo.png", 'data');

        PendingShopRedaction::create([
            'shop_domain' => $shopDomain,
            'store_id' => $storeId,
            'attempts' => 1,
            'last_attempt_at' => now()->subMinutes(10),
            'last_error' => 'Previous failure',
        ]);

        $this->assertNull(ShopifyStore::where('shop', $shopDomain)->first());
        $this->assertTrue(Storage::disk('public')->exists("logos/{$storeId}/lingering_logo.png"));

        // Shopify retries delivery of shop/redact
        $payload = [
            'shop_domain' => $shopDomain,
        ];

        $response = $this->postWebhook('shop/redact', $shopDomain, $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Shop data and pending files erased successfully',
        ]);

        // Lingering files MUST be deleted
        $this->assertFalse(Storage::disk('public')->exists("logos/{$storeId}"));

        // Tracking record MUST be deleted from database
        $this->assertDatabaseMissing('pending_shop_redactions', [
            'shop_domain' => $shopDomain,
        ]);
    }

    public function test_20_another_shops_files_remain_untouched_during_shop_redaction(): void
    {
        $storeA = ShopifyStore::factory()->create(['shop' => 'store-victim.myshopify.com']);
        $storeB = ShopifyStore::factory()->create(['shop' => 'store-survivor.myshopify.com']);

        Storage::disk('public')->put("logos/{$storeA->id}/victim_icon.svg", '<svg>A</svg>');
        Storage::disk('public')->put("logos/{$storeB->id}/survivor_icon.svg", '<svg>B</svg>');

        $payload = [
            'shop_id' => $storeA->id,
            'shop_domain' => 'store-victim.myshopify.com',
        ];

        $response = $this->postWebhook('shop/redact', 'store-victim.myshopify.com', $payload);

        $response->assertStatus(200);

        // Store A files removed
        $this->assertFalse(Storage::disk('public')->exists("logos/{$storeA->id}"));

        // Store B files remain untouched
        $this->assertTrue(Storage::disk('public')->exists("logos/{$storeB->id}/survivor_icon.svg"));
    }

    public function test_21_retry_command_successfully_cleans_pending_directories(): void
    {
        $storeId = 888;
        $shopDomain = 'cmd-clean.myshopify.com';

        Storage::disk('public')->put("logos/{$storeId}/old_logo.png", 'data');

        PendingShopRedaction::create([
            'shop_domain' => $shopDomain,
            'store_id' => $storeId,
            'attempts' => 1,
            'last_attempt_at' => now()->subMinutes(30),
        ]);

        $this->assertTrue(Storage::disk('public')->exists("logos/{$storeId}/old_logo.png"));

        // Execute scheduled retry command
        $this->artisan('gdpr:retry-file-cleanups')->assertSuccessful();

        // Files purged
        $this->assertFalse(Storage::disk('public')->exists("logos/{$storeId}"));

        // Tracking row deleted
        $this->assertDatabaseMissing('pending_shop_redactions', [
            'shop_domain' => $shopDomain,
        ]);
    }

    public function test_22_retry_command_increments_attempts_on_failure(): void
    {
        $storeId = 889;
        $shopDomain = 'cmd-fail.myshopify.com';

        PendingShopRedaction::create([
            'shop_domain' => $shopDomain,
            'store_id' => $storeId,
            'attempts' => 0,
        ]);

        // Partial mock to force purge failure
        $this->partialMock(ShopRedactionService::class, function ($mock) use ($storeId) {
            $mock->shouldReceive('purgeShopFilesystem')->with($storeId)->andReturn(false);
        });

        $this->artisan('gdpr:retry-file-cleanups')->assertSuccessful();

        // Record must remain in database with attempts incremented to 1
        $this->assertDatabaseHas('pending_shop_redactions', [
            'shop_domain' => $shopDomain,
            'store_id' => $storeId,
            'attempts' => 1,
            'last_error' => 'Filesystem purge did not completely remove all directories',
        ]);
    }

    public function test_23_retry_command_continues_processing_if_one_shop_fails(): void
    {
        $storeAId = 890;
        $storeBId = 891;

        PendingShopRedaction::create([
            'shop_domain' => 'store-a.myshopify.com',
            'store_id' => $storeAId,
            'attempts' => 0,
        ]);

        PendingShopRedaction::create([
            'shop_domain' => 'store-b.myshopify.com',
            'store_id' => $storeBId,
            'attempts' => 0,
        ]);

        Storage::disk('public')->put("logos/{$storeBId}/b.svg", '<svg></svg>');

        // Mock: store A fails, store B succeeds
        $this->partialMock(ShopRedactionService::class, function ($mock) use ($storeAId, $storeBId) {
            $mock->shouldReceive('purgeShopFilesystem')->with($storeAId)->andReturn(false);
            $mock->shouldReceive('purgeShopFilesystem')->with($storeBId)->andReturn(true);
        });

        $this->artisan('gdpr:retry-file-cleanups')->assertSuccessful();

        // Store A remains in pending queue
        $this->assertDatabaseHas('pending_shop_redactions', [
            'shop_domain' => 'store-a.myshopify.com',
            'store_id' => $storeAId,
            'attempts' => 1,
        ]);

        // Store B was successfully cleaned and removed
        $this->assertDatabaseMissing('pending_shop_redactions', [
            'shop_domain' => 'store-b.myshopify.com',
        ]);
    }

    public function test_24_repeated_command_execution_is_idempotent(): void
    {
        $this->artisan('gdpr:retry-file-cleanups')
            ->expectsOutput('No pending GDPR shop redactions found.')
            ->assertSuccessful();

        $this->artisan('gdpr:retry-file-cleanups')
            ->expectsOutput('No pending GDPR shop redactions found.')
            ->assertSuccessful();
    }
}
