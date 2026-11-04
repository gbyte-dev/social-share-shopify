<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\WishlistItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class WishlistStorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = 'test_shopify_app_secret_abc123';
        config(['shopify.api_secret' => $this->secret]);

        $this->storeA = ShopifyStore::factory()->create([
            'shop' => 'store-alpha.myshopify.com',
            'is_active' => true,
        ]);

        $this->storeB = ShopifyStore::factory()->create([
            'shop' => 'store-beta.myshopify.com',
            'is_active' => true,
        ]);
    }

    /**
     * Helper to build and HMAC-sign Shopify App Proxy query parameters.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function signParams(array $params, ?string $secret = null): array
    {
        $secret = $secret ?? $this->secret;
        unset($params['signature']);
        ksort($params);

        $str = '';
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $v = implode(',', $v);
            }
            $str .= $k.'='.$v;
        }

        $params['signature'] = hash_hmac('sha256', $str, $secret);

        return $params;
    }

    /**
     * Base signed params for storeA.
     *
     * @return array<string, mixed>
     */
    protected function baseParams(ShopifyStore $store, ?string $customerId = null): array
    {
        $params = [
            'shop' => $store->shop,
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
        ];

        if ($customerId !== null && trim($customerId) !== '') {
            $params['logged_in_customer_id'] = $customerId;
        }

        return $params;
    }

    public function test_proxy_request_without_signature_is_unauthorized(): void
    {
        $response = $this->getJson('/shopify/proxy/wishlist?shop='.$this->storeA->shop);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'code' => 'MISSING_SIGNATURE',
        ]);
    }

    public function test_proxy_request_with_invalid_signature_is_unauthorized(): void
    {
        $params = $this->baseParams($this->storeA);
        $params['signature'] = 'invalid_tampered_hmac_signature';

        $response = $this->getJson('/shopify/proxy/wishlist?'.http_build_query($params));

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'code' => 'INVALID_SIGNATURE',
        ]);
    }

    public function test_proxy_request_with_expired_timestamp_is_unauthorized(): void
    {
        $params = [
            'shop' => $this->storeA->shop,
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) (time() - 90000), // > 24 hours ago
        ];
        $signed = $this->signParams($params);

        $response = $this->getJson('/shopify/proxy/wishlist?'.http_build_query($signed));

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'code' => 'INVALID_TIMESTAMP',
        ]);
    }

    public function test_proxy_request_with_unknown_shop_returns_404(): void
    {
        $params = [
            'shop' => 'nonexistent-shop.myshopify.com',
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
        ];
        $signed = $this->signParams($params);

        $response = $this->getJson('/shopify/proxy/wishlist?'.http_build_query($signed));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'code' => 'STORE_NOT_FOUND',
        ]);
    }

    public function test_guest_can_fetch_empty_wishlist_with_valid_signature_and_guest_uuid(): void
    {
        $guestUuid = (string) Str::uuid();
        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/shopify/proxy/wishlist?'.http_build_query($signed));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'items' => [],
            'count' => 0,
            'is_customer' => false,
        ]);
    }

    public function test_guest_missing_identity_returns_422(): void
    {
        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        $response = $this->getJson('/shopify/proxy/wishlist?'.http_build_query($signed));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'code' => 'MISSING_IDENTITY',
        ]);
    }

    public function test_guest_can_add_product_to_database_wishlist(): void
    {
        $guestUuid = (string) Str::uuid();
        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        $payload = [
            'product_id' => '88880001',
            'variant_id' => '44440001',
            'title' => 'Vintage Denim Jacket',
            'handle' => 'vintage-denim-jacket',
            'price' => '$89.00',
            'url' => '/products/vintage-denim-jacket',
            'img' => 'https://cdn.shopify.com/products/jacket.jpg',
            'currency' => 'USD',
            'guest_uuid' => $guestUuid,
        ];

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/add?'.http_build_query($signed), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'created' => true,
            'count' => 1,
            'item' => [
                'id' => '88880001',
                'product_id' => '88880001',
                'title' => 'Vintage Denim Jacket',
                'handle' => 'vintage-denim-jacket',
                'price' => '$89.00',
            ],
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880001',
            'product_title' => 'Vintage Denim Jacket',
        ]);
    }

    public function test_adding_duplicate_product_does_not_create_duplicate_records(): void
    {
        $guestUuid = (string) Str::uuid();
        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        $payload = [
            'product_id' => '88880002',
            'title' => 'Classic Cotton Tee',
            'guest_uuid' => $guestUuid,
        ];

        // First call
        $first = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/add?'.http_build_query($signed), $payload);
        $first->assertStatus(200);
        $this->assertTrue($first->json('created'));
        $this->assertSame(1, $first->json('count'));

        // Second call with same product
        $second = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/add?'.http_build_query($signed), $payload);
        $second->assertStatus(200);
        $this->assertFalse($second->json('created'));
        $this->assertSame(1, $second->json('count'));

        // Database must only have 1 record
        $this->assertSame(1, WishlistItem::where('shop_id', $this->storeA->id)
            ->where('guest_uuid', $guestUuid)
            ->where('product_id', '88880002')
            ->count());
    }

    public function test_guest_can_check_wishlist_status_and_count(): void
    {
        $guestUuid = (string) Str::uuid();
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880003',
        ]);

        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        // Check count
        $countRes = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/shopify/proxy/wishlist/count?'.http_build_query($signed));
        $countRes->assertStatus(200);
        $countRes->assertJson(['success' => true, 'count' => 1]);

        // Check single status: in wishlist
        $statusSigned = $this->signParams(array_merge($params, ['product_id' => '88880003']));
        $statusRes = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/shopify/proxy/wishlist/status?'.http_build_query($statusSigned));
        $statusRes->assertStatus(200);
        $statusRes->assertJson(['success' => true, 'in_wishlist' => true, 'count' => 1]);

        // Check single status: not in wishlist
        $statusNotSigned = $this->signParams(array_merge($params, ['product_id' => '99999999']));
        $statusNotRes = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/shopify/proxy/wishlist/status?'.http_build_query($statusNotSigned));
        $statusNotRes->assertStatus(200);
        $statusNotRes->assertJson(['success' => true, 'in_wishlist' => false, 'count' => 1]);
    }

    public function test_guest_can_remove_product_from_wishlist(): void
    {
        $guestUuid = (string) Str::uuid();
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880004',
        ]);

        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/remove?'.http_build_query($signed), [
                'product_id' => '88880004',
                'guest_uuid' => $guestUuid,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'removed' => true, 'count' => 0]);

        $this->assertDatabaseMissing('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'product_id' => '88880004',
        ]);
    }

    public function test_logged_in_customer_wishlist_persists_under_customer_id(): void
    {
        $customerId = '7123456789';
        $params = $this->baseParams($this->storeA, $customerId);
        $signed = $this->signParams($params);

        $response = $this->postJson('/shopify/proxy/wishlist/add?'.http_build_query($signed), [
            'product_id' => '88880005',
            'title' => 'Customer Exclusive Silk Shirt',
            'price' => '$120.00',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'created' => true,
            'count' => 1,
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'customer_id' => $customerId,
            'guest_uuid' => null,
            'product_id' => '88880005',
        ]);
    }

    public function test_customer_cannot_see_or_modify_other_customer_wishlist(): void
    {
        $customerOne = '7000000001';
        $customerTwo = '7000000002';

        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'customer_id' => $customerOne,
            'guest_uuid' => null,
            'product_id' => '88880006',
            'product_title' => 'Customer One Secret Item',
        ]);

        // Customer Two requests their wishlist
        $paramsTwo = $this->baseParams($this->storeA, $customerTwo);
        $signedTwo = $this->signParams($paramsTwo);

        $response = $this->getJson('/shopify/proxy/wishlist?'.http_build_query($signedTwo));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 0,
            'items' => [],
        ]);

        // Even if Customer Two tries to tamper customer_id in POST body, backend only uses verified context
        $removeResponse = $this->postJson('/shopify/proxy/wishlist/remove?'.http_build_query($signedTwo), [
            'product_id' => '88880006',
            'customer_id' => $customerOne, // Untrusted spoof attempt
        ]);

        $removeResponse->assertStatus(200);
        $removeResponse->assertJson(['success' => true, 'removed' => false, 'count' => 0]);

        // Item still exists for Customer One
        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'customer_id' => $customerOne,
            'product_id' => '88880006',
        ]);
    }

    public function test_wishlists_are_strictly_isolated_between_different_shopify_stores(): void
    {
        $guestUuid = (string) Str::uuid();

        // Add item to Store A
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880007',
            'product_title' => 'Store A Only Item',
        ]);

        // Store B queries with same guestUuid
        $paramsB = $this->baseParams($this->storeB);
        $signedB = $this->signParams($paramsB);

        $responseB = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/shopify/proxy/wishlist?'.http_build_query($signedB));

        $responseB->assertStatus(200);
        $responseB->assertJson([
            'success' => true,
            'count' => 0,
            'items' => [],
        ]);
    }

    public function test_guest_to_customer_wishlist_merge_after_login(): void
    {
        $guestUuid = (string) Str::uuid();
        $customerId = '7111222333';

        // Guest saved 2 products before logging in
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880008',
            'product_title' => 'Guest Item 1',
        ]);
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880009',
            'product_title' => 'Guest Item 2',
        ]);

        // Customer logs in and calls merge
        $params = $this->baseParams($this->storeA, $customerId);
        $signed = $this->signParams($params);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/merge?'.http_build_query($signed), [
                'guest_uuid' => $guestUuid,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'merged_count' => 2,
            'total_count' => 2,
        ]);

        // All items should now belong to customer_id with guest_uuid = null
        $this->assertSame(2, WishlistItem::where('shop_id', $this->storeA->id)
            ->where('customer_id', $customerId)
            ->whereNull('guest_uuid')
            ->count());

        // Zero items left for guestUuid
        $this->assertSame(0, WishlistItem::where('shop_id', $this->storeA->id)
            ->where('guest_uuid', $guestUuid)
            ->count());
    }

    public function test_guest_to_customer_merge_avoids_duplicate_products_and_cleans_up_guest_records(): void
    {
        $guestUuid = (string) Str::uuid();
        $customerId = '7222333444';

        // Customer already has item '88880010'
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'customer_id' => $customerId,
            'guest_uuid' => null,
            'product_id' => '88880010',
            'product_title' => 'Existing Customer Item',
        ]);

        // Guest had the same item '88880010' and a new item '88880011'
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880010',
            'product_title' => 'Duplicate Guest Item',
        ]);
        WishlistItem::factory()->create([
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880011',
            'product_title' => 'Unique Guest Item',
        ]);

        $params = $this->baseParams($this->storeA, $customerId);
        $signed = $this->signParams($params);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/merge?'.http_build_query($signed), [
                'guest_uuid' => $guestUuid,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'merged_count' => 1, // Only 1 new item migrated
            'total_count' => 2,  // Customer total is 2
        ]);

        // Customer now has exactly 2 unique items
        $this->assertSame(2, WishlistItem::where('shop_id', $this->storeA->id)
            ->where('customer_id', $customerId)
            ->count());

        // Guest items are completely removed
        $this->assertSame(0, WishlistItem::where('shop_id', $this->storeA->id)
            ->where('guest_uuid', $guestUuid)
            ->count());
    }

    public function test_guest_to_customer_merge_never_leaks_items_across_different_shopify_stores(): void
    {
        $guestUuid = (string) Str::uuid();
        $customerId = '7333444555';

        // Guest item created on Store B
        WishlistItem::factory()->create([
            'shop_id' => $this->storeB->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => '88880012',
            'product_title' => 'Store B Guest Product',
        ]);

        // Customer merges on Store A
        $paramsA = $this->baseParams($this->storeA, $customerId);
        $signedA = $this->signParams($paramsA);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/merge?'.http_build_query($signedA), [
                'guest_uuid' => $guestUuid,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'merged_count' => 0,
            'total_count' => 0,
        ]);

        // Store B item must remain untouched on Store B
        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeB->id,
            'guest_uuid' => $guestUuid,
            'product_id' => '88880012',
        ]);
    }

    public function test_safe_one_time_local_storage_migration_into_database(): void
    {
        $guestUuid = (string) Str::uuid();
        $params = $this->baseParams($this->storeA);
        $signed = $this->signParams($params);

        $legacyItems = [
            [
                'id' => '88880020',
                'handle' => 'local-leather-boots',
                'title' => 'Local Leather Boots',
                'price' => '$149.00',
                'img' => 'https://cdn.shopify.com/products/boots.jpg',
                'url' => '/products/local-leather-boots',
            ],
            [
                'id' => '88880021',
                'handle' => 'local-wool-scarf',
                'title' => 'Local Wool Scarf',
                'price' => '$39.00',
                'img' => 'https://cdn.shopify.com/products/scarf.jpg',
                'url' => '/products/local-wool-scarf',
            ],
        ];

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->postJson('/shopify/proxy/wishlist/migrate-local?'.http_build_query($signed), [
                'items' => $legacyItems,
                'guest_uuid' => $guestUuid,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'migrated_count' => 2,
            'total_count' => 2,
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'product_id' => '88880020',
            'product_title' => 'Local Leather Boots',
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'product_id' => '88880021',
            'product_title' => 'Local Wool Scarf',
        ]);
    }

    public function test_single_identity_model_constraint_fails_if_both_or_neither_identity_set(): void
    {
        // Expect failure when neither customer_id nor guest_uuid is set
        $this->expectException(InvalidArgumentException::class);
        WishlistItem::create([
            'shop_id' => $this->storeA->id,
            'customer_id' => null,
            'guest_uuid' => null,
            'product_id' => '88880030',
        ]);
    }

    public function test_single_identity_model_constraint_fails_if_both_identities_set(): void
    {
        // Expect failure when both customer_id and guest_uuid are set
        $this->expectException(InvalidArgumentException::class);
        WishlistItem::create([
            'shop_id' => $this->storeA->id,
            'customer_id' => '7999888777',
            'guest_uuid' => (string) Str::uuid(),
            'product_id' => '88880031',
        ]);
    }

    public function test_database_level_unique_constraint_blocks_duplicate_customer_products(): void
    {
        $customerId = '7555666777';

        WishlistItem::create([
            'shop_id' => $this->storeA->id,
            'customer_id' => $customerId,
            'guest_uuid' => null,
            'product_id' => '88880032',
        ]);

        $this->expectException(QueryException::class);
        WishlistItem::create([
            'shop_id' => $this->storeA->id,
            'customer_id' => $customerId,
            'guest_uuid' => null,
            'product_id' => '88880032',
        ]);
    }

    public function test_database_level_unique_constraint_blocks_duplicate_guest_products(): void
    {
        $guestUuid = (string) Str::uuid();

        WishlistItem::create([
            'shop_id' => $this->storeA->id,
            'customer_id' => null,
            'guest_uuid' => $guestUuid,
            'product_id' => '88880033',
        ]);

        $this->expectException(QueryException::class);
        WishlistItem::create([
            'shop_id' => $this->storeA->id,
            'customer_id' => null,
            'guest_uuid' => $guestUuid,
            'product_id' => '88880033',
        ]);
    }

    public function test_direct_storefront_api_allows_guest_add_with_valid_shop(): void
    {
        $guestUuid = (string) Str::uuid();

        $response = $this->postJson('/api/storefront/wishlist/add', [
            'shop' => $this->storeA->shop,
            'guest_uuid' => $guestUuid,
            'product_id' => 'direct-test-999',
            'title' => 'Direct API Product',
            'price' => '$29.99',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'created' => true,
            'count' => 1,
            'item' => [
                'product_id' => 'direct-test-999',
                'title' => 'Direct API Product',
            ],
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => 'direct-test-999',
        ]);
    }

    public function test_direct_storefront_api_rejects_missing_or_invalid_shop(): void
    {
        $response = $this->postJson('/api/storefront/wishlist/add', [
            'guest_uuid' => (string) Str::uuid(),
            'product_id' => 'direct-test-1000',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'code' => 'MISSING_SHOP',
        ]);

        $responseInvalid = $this->postJson('/api/storefront/wishlist/add', [
            'shop' => 'nonexistent-shop.myshopify.com',
            'guest_uuid' => (string) Str::uuid(),
            'product_id' => 'direct-test-1000',
        ]);

        $responseInvalid->assertStatus(404);
        $responseInvalid->assertJson([
            'success' => false,
            'code' => 'STORE_NOT_FOUND',
        ]);
    }

    public function test_direct_storefront_api_ignores_spoofed_customer_id_and_uses_guest(): void
    {
        $guestUuid = (string) Str::uuid();

        $response = $this->postJson('/api/storefront/wishlist/add', [
            'shop' => $this->storeA->shop,
            'guest_uuid' => $guestUuid,
            'customer_id' => 'spoofed-customer-id-999',
            'product_id' => 'direct-test-1001',
        ]);

        $response->assertStatus(200);

        // Crucial security check: Spoofed customer ID without Shopify HMAC signature must NOT be saved
        $this->assertDatabaseHas('wishlist_items', [
            'shop_id' => $this->storeA->id,
            'guest_uuid' => $guestUuid,
            'customer_id' => null,
            'product_id' => 'direct-test-1001',
        ]);
    }
}
