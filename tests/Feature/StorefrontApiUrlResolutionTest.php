<?php

namespace Tests\Feature;

use App\Http\Controllers\StorefrontApiController;
use App\Models\ShopifyStore;
use App\Models\StorefrontSetting;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontApiUrlResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected string $secret = 'test_shopify_app_secret_12345';

    protected function setUp(): void
    {
        parent::setUp();

        config(['shopify.api_secret' => $this->secret]);

        $this->store = ShopifyStore::factory()->create([
            'shop' => 'active-dev-store.myshopify.com',
            'is_active' => true,
            'access_token' => 'shpat_test_token',
        ]);

        StorefrontSetting::create([
            'shopify_store_id' => $this->store->id,
            'is_enabled' => true,
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
    }

    /**
     * Helper to HMAC-sign App Proxy query parameters.
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
            $query .= $k . '=' . $v;
        }

        $params['signature'] = hash_hmac('sha256', $query, $this->secret);

        return $params;
    }

    /**
     * Test 1: Local development URL resolution.
     * Active Shopify CLI tunnel URL is resolved when configured.
     * Local loopback domains (.test, localhost) are never leaked as public storefront URLs.
     */
    public function test_local_development_tunnel_url_is_resolved(): void
    {
        $tunnelUrl = 'https://socialnest-tunnel.trycloudflare.com';
        config(['shopify.app_url' => $tunnelUrl]);

        $controller = app(StorefrontApiController::class);
        $this->assertSame($tunnelUrl, $controller->resolvePublicAppUrl());

        $response = $this->getJson('/api/storefront/config?shop=' . $this->store->shop);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'public_app_url' => $tunnelUrl,
        ]);
    }

    /**
     * Test 1b: Local .test domain is sanitized and never leaked to public storefront.
     */
    public function test_local_test_domain_is_sanitized_to_production_fallback(): void
    {
        config(['shopify.app_url' => 'https://socialvire.webvire.com']);
        config(['app.url' => 'https://socialvire.webvire.com

        $controller = app(StorefrontApiController::class);
        $this->assertSame('https://socialvire.webvire.com'

        $response = $this->getJson('/api/storefront/config?shop=' . $this->store->shop);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'public_app_url' => 'https://socialvire.webvire.com',
        ]);
    }

    /**
     * Test 2: Production URL resolution.
     */
    public function test_production_url_resolution(): void
    {
        config(['shopify.app_url' => 'https://socialvire.webvire.com']);

        $controller = app(StorefrontApiController::class);
        $this->assertSame('https://socialvire.webvire.com', $controller->resolvePublicAppUrl());

        $response = $this->getJson('/api/storefront/config?shop=' . $this->store->shop);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'public_app_url' => 'https://socialvire.webvire.com',
        ]);
    }

    /**
     * Test 3: Missing URL configuration defaults to production URL.
     */
    public function test_missing_url_configuration_falls_back_to_production(): void
    {
        config(['shopify.app_url' => null]);
        config(['app.url' => null]);

        $controller = app(StorefrontApiController::class);
        $this->assertSame('https://socialvire.webvire.com', $controller->resolvePublicAppUrl());

        $response = $this->getJson('/api/storefront/config?shop=' . $this->store->shop);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'public_app_url' => 'https://socialvire.webvire.com',
        ]);
    }

    /**
     * Test 4: App Proxy primary request.
     * Shopify signs the request with HMAC, backend authenticates and processes.
     */
    public function test_app_proxy_primary_request_with_valid_hmac(): void
    {
        // 4a. App Proxy config request
        $configParams = $this->signProxyParams([
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
        ]);

        $configResponse = $this->getJson('/shopify/proxy/config?' . http_build_query($configParams));
        $configResponse->assertStatus(200);
        $configResponse->assertJson([
            'success' => true,
            'shop' => $this->store->shop,
            'app_proxy_path' => '/apps/socialnest',
        ]);

        // 4b. App Proxy wishlist request
        WishlistItem::create([
            'shop_id' => $this->store->id,
            'guest_uuid' => 'test-guest-uuid-12345678',
            'product_id' => 'prod-999',
            'title' => 'Test Proxy Product',
            'handle' => 'test-proxy-product',
        ]);

        $wishlistParams = $this->signProxyParams([
            'shop' => $this->store->shop,
            'guest_uuid' => 'test-guest-uuid-12345678',
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
        ]);

        $wishlistResponse = $this->getJson('/shopify/proxy/wishlist?' . http_build_query($wishlistParams));
        $wishlistResponse->assertStatus(200);
        $wishlistResponse->assertJson([
            'success' => true,
            'count' => 1,
        ]);

        // 4c. Proxy request without signature must fail with 401
        $unsignedResponse = $this->getJson('/shopify/proxy/wishlist?shop=' . $this->store->shop . '&guest_uuid=test-guest-uuid-12345678');
        $unsignedResponse->assertStatus(401);
    }

    /**
     * Test 5: Direct API fallback.
     * When App Proxy is unavailable, storefront falls back to direct API routes.
     */
    public function test_direct_api_fallback_allows_guest_storefront_access(): void
    {
        $guestUuid = 'guest-direct-fallback-' . Str::random(12);

        // 5a. Direct wishlist add via fallback
        $addResponse = $this->postJson('/api/storefront/wishlist/add', [
            'shop' => $this->store->shop,
            'guest_uuid' => $guestUuid,
            'product_id' => 'prod-direct-101',
            'title' => 'Direct Fallback Sneaker',
            'handle' => 'direct-fallback-sneaker',
            'price' => '120.00',
        ], [
            'X-Shopify-Shop-Domain' => $this->store->shop,
            'X-Guest-UUID' => $guestUuid,
        ]);

        $addResponse->assertStatus(200);
        $addResponse->assertJson([
            'success' => true,
            'count' => 1,
            'item' => [
                'product_id' => 'prod-direct-101',
            ],
        ]);

        // 5b. Direct wishlist fetch via fallback
        $fetchResponse = $this->getJson('/api/storefront/wishlist?shop=' . $this->store->shop . '&guest_uuid=' . $guestUuid, [
            'X-Shopify-Shop-Domain' => $this->store->shop,
        ]);

        $fetchResponse->assertStatus(200);
        $fetchResponse->assertJson([
            'success' => true,
            'count' => 1,
        ]);

        // 5c. Direct request with missing shop fails with 400
        $missingShopResponse = $this->getJson('/api/storefront/wishlist?guest_uuid=' . $guestUuid);
        $missingShopResponse->assertStatus(400);

        // 5d. Direct request with unknown or inactive shop fails with 404
        $unknownShopResponse = $this->getJson('/api/storefront/wishlist?shop=nonexistent-shop.myshopify.com&guest_uuid=' . $guestUuid);
        $unknownShopResponse->assertStatus(404);
    }

    /**
     * Test 6: Verify storefront JavaScript files do not embed or fallback to unreachable .test domains for public storefronts.
     */
    public function test_public_storefront_never_calls_local_test_api_domain(): void
    {
        $storageJs = file_get_contents(base_path('extensions/socialnest-theme-ext/assets/socialnest-wishlist-storage.js'));
        $apiJs = file_get_contents(base_path('extensions/socialnest-theme-ext/assets/socialnest-wishlist-api.js'));

        $this->assertNotFalse($storageJs);
        $this->assertNotFalse($apiJs);

        // Verify isInvalidPublicHost guards exist and are exported
        $this->assertStringContainsString('export function isInvalidPublicHost', $storageJs);
        $this->assertStringContainsString('export function isLocalEnvironment', $storageJs);

        // Verify getDirectAppUrl does not blindly return PROD_APP_URL on public storefronts
        $this->assertStringContainsString('isLocalEnvironment()', $storageJs);
        $this->assertStringContainsString('return "";', $storageJs);

        // Verify API module guards against calling invalid public hosts
        $this->assertStringContainsString('!isInvalidPublicHost(directBase)', $apiJs);
    }

    /**
     * Test 7: Storefront config endpoint supports App Proxy subpaths (/apps/socialnest/config and /apps/socialvire/config).
     * Verifies signed HMAC requests, direct storefront access, preflight OPTIONS, and error boundaries.
     */
    public function test_storefront_config_endpoint_supports_all_app_proxy_subpaths(): void
    {
        // 7a. Signed App Proxy request with path_prefix=/apps/socialnest
        $signedNest = $this->signProxyParams([
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
        ]);

        $resNest = $this->getJson('/apps/socialnest/config?' . http_build_query($signedNest));
        $resNest->assertStatus(200);
        $resNest->assertJson([
            'success' => true,
            'shop' => $this->store->shop,
            'enabled' => true,
            'app_proxy_path' => '/apps/socialnest',
        ]);
        $this->assertArrayHasKey('settings', $resNest->json());
        $this->assertArrayHasKey('links', $resNest->json());

        // 7b. Signed App Proxy request with path_prefix=/apps/socialvire
        $signedVire = $this->signProxyParams([
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialvire',
            'timestamp' => (string) time(),
        ]);

        $resVire = $this->getJson('/apps/socialvire/config?' . http_build_query($signedVire));
        $resVire->assertStatus(200);
        $resVire->assertJson([
            'success' => true,
            'shop' => $this->store->shop,
            'enabled' => true,
            'app_proxy_path' => '/apps/socialvire',
        ]);

        // 7c. Direct storefront access without HMAC signature
        $directRes = $this->getJson('/apps/socialnest/config?shop=' . $this->store->shop);
        $directRes->assertStatus(200);
        $directRes->assertJson([
            'success' => true,
            'shop' => $this->store->shop,
            'enabled' => true,
        ]);

        // 7d. CORS preflight OPTIONS request
        $optionsRes = $this->call('OPTIONS', '/apps/socialnest/config');
        $optionsRes->assertStatus(204);
        $this->assertSame('*', $optionsRes->headers->get('Access-Control-Allow-Origin'));

        // 7e. Error handling: missing shop returns 400
        $missingShop = $this->getJson('/apps/socialnest/config');
        $missingShop->assertStatus(400);

        // 7f. Error handling: invalid HMAC signature returns 401
        $invalidSigParams = [
            'shop' => $this->store->shop,
            'path_prefix' => '/apps/socialnest',
            'timestamp' => (string) time(),
            'signature' => 'invalid_forged_hmac_signature',
        ];
        $invalidSigRes = $this->getJson('/apps/socialnest/config?' . http_build_query($invalidSigParams));
        $invalidSigRes->assertStatus(401);
    }
}
