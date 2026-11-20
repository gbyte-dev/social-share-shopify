<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WishlistRenderingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected string $secret = 'test_shopify_app_secret_987654';

    protected function setUp(): void
    {
        parent::setUp();

        config(['shopify.api_secret' => $this->secret]);

        $this->store = ShopifyStore::factory()->create([
            'shop' => '26-sep.myshopify.com',
            'is_active' => true,
            'access_token' => 'shpat_live_test_token',
        ]);
    }

    /**
     * Helper to compute Shopify App Proxy HMAC-SHA256 signature.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function signProxyParams(array $params): array
    {
        unset($params['signature']);
        ksort($params);

        $query = '';
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $v = implode(',', $v);
            }
            $query .= $k.'='.$v;
        }

        $params['signature'] = hash_hmac('sha256', $query, $this->secret);

        return $params;
    }

    /**
     * Test 1: Config request success.
     * Verifies /apps/socialnest/config returns 200 with valid settings and social links.
     */
    public function test_config_request_succeeds_with_valid_shop(): void
    {
        $signed = $this->signProxyParams([
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
        ]);

        $response = $this->getJson('/apps/socialnest/config?'.http_build_query($signed));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shop' => $this->store->shop,
            'enabled' => true,
            'app_proxy_path' => '/apps/socialnest',
        ]);
        $response->assertJsonStructure([
            'success',
            'shop',
            'enabled',
            'public_app_url',
            'app_proxy_path',
            'settings',
            'links',
        ]);
    }

    /**
     * Test 2: Config request failure.
     * Verifies /apps/socialnest/config rejects requests with missing or invalid shop parameters.
     */
    public function test_config_request_fails_with_missing_shop(): void
    {
        $response = $this->getJson('/apps/socialnest/config');

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Test 3: Empty wishlist.
     * Verifies /apps/socialnest/wishlist returns empty items array and count = 0.
     */
    public function test_empty_wishlist_returns_zero_items(): void
    {
        $guestUuid = (string) Str::uuid();
        $signed = $this->signProxyParams([
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'guest_uuid' => $guestUuid,
            'timestamp' => (string) time(),
        ]);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/apps/socialnest/wishlist?'.http_build_query($signed));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'items' => [],
            'count' => 0,
        ]);
    }

    /**
     * Test 4: Wishlist containing products.
     * Verifies /apps/socialnest/wishlist returns saved products with full card metadata.
     */
    public function test_wishlist_with_products_returns_populated_array(): void
    {
        $guestUuid = (string) Str::uuid();

        WishlistItem::create([
            'shop_id' => $this->store->id,
            'guest_uuid' => $guestUuid,
            'product_id' => 'prod-test-999',
            'product_handle' => 'oversized-hoodie',
            'product_title' => 'Oversized Organic Hoodie',
            'product_price' => '$75.00',
            'product_image' => 'https://cdn.shopify.com/hoodie.jpg',
        ]);

        $signed = $this->signProxyParams([
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'guest_uuid' => $guestUuid,
            'timestamp' => (string) time(),
        ]);

        $response = $this->withHeaders(['X-Guest-UUID' => $guestUuid])
            ->getJson('/apps/socialnest/wishlist?'.http_build_query($signed));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonPath('items.0.product_id', 'prod-test-999');
        $response->assertJsonPath('items.0.title', 'Oversized Organic Hoodie');
    }

    /**
     * Test 5: Invalid App Proxy signature.
     * Verifies forged HMAC signatures are strictly rejected with 401.
     */
    public function test_invalid_app_proxy_signature_is_rejected(): void
    {
        $guestUuid = (string) Str::uuid();
        $params = [
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'guest_uuid' => $guestUuid,
            'timestamp' => (string) time(),
            'signature' => 'forged_invalid_hmac_sha256',
        ];

        $response = $this->getJson('/apps/socialnest/wishlist?'.http_build_query($params));

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'code' => 'INVALID_SIGNATURE',
        ]);
    }

    /**
     * Test 6: Missing guest identity.
     * Verifies direct storefront wishlist requests without customer_id or guest_uuid fail with 422.
     */
    public function test_missing_guest_identity_returns_422(): void
    {
        $response = $this->getJson('/api/storefront/wishlist?shop='.$this->store->shop);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'code' => 'MISSING_IDENTITY',
        ]);
    }

    /**
     * Test 7: JavaScript initialization contract in Shopify Theme Editor.
     * Verifies socialnest-wishlist-page.js declares win, handles designMode, and avoids ReferenceError.
     */
    public function test_javascript_initialization_contract_for_theme_editor(): void
    {
        $pageJs = file_get_contents(base_path('extensions/socialnest-theme-ext/assets/socialnest-wishlist-page.js'));

        $this->assertNotFalse($pageJs);
        // Verify win declaration
        $this->assertStringContainsString('var win = typeof window !== \'undefined\' ? window : null;', $pageJs);
        // Verify designMode defense
        $this->assertStringContainsString('isDesignMode', $pageJs);
        $this->assertStringContainsString('Shopify.designMode', $pageJs);
        // Verify error boundary
        $this->assertStringContainsString('try {', $pageJs);
        $this->assertStringContainsString('catch (err) {', $pageJs);
    }

    /**
     * Test 8: JavaScript initialization contract on the live storefront.
     * Verifies wishlist-page.liquid injects type="module" script and eliminates incompatible schema javascript.
     */
    public function test_javascript_initialization_contract_on_live_storefront(): void
    {
        $liquid = file_get_contents(base_path('extensions/socialnest-theme-ext/blocks/wishlist-page.liquid'));

        $this->assertNotFalse($liquid);
        // Verify module script tag exists
        $this->assertStringContainsString('<script type="module" src="{{ \'socialnest-wishlist.js\' | asset_url }}"></script>', $liquid);
        // Verify incompatible schema javascript was removed
        $this->assertStringNotContainsString('"javascript": "socialnest-wishlist.js"', $liquid);
        // Verify target handle and dedicated container
        $this->assertStringContainsString('socialnest-wishlist-page-wrapper', $liquid);
        $this->assertStringContainsString('socialnest-wishlist-grid-root', $liquid);
    }
}
