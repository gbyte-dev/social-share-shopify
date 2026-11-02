<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ShopifyAppEntryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected string $testApiKey = 'test_entry_key_123';

    protected string $testApiSecret = 'test_entry_secret_456';

    protected string $testAppUrl = 'https://socialnest.test';

    protected string $testRedirectUri = 'https://socialnest.test/shopify/callback';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shopify.api_key', $this->testApiKey);
        Config::set('shopify.api_secret', $this->testApiSecret);
        Config::set('shopify.app_url', $this->testAppUrl);
        Config::set('shopify.redirect_uri', $this->testRedirectUri);
        Config::set('shopify.api_scopes', 'read_products');
        Config::set('shopify.webhook_secret', $this->testApiSecret);
    }

    /**
     * Helper to compute valid Shopify launch HMAC signature.
     */
    protected function generateValidHmacParams(array $params): array
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $val) {
            $pairs[] = "{$key}={$val}";
        }
        $queryString = implode('&', $pairs);
        $params['hmac'] = hash_hmac('sha256', $queryString, $this->testApiSecret);

        return $params;
    }

    public function test_public_visitor_without_shop_reaches_marketing_homepage(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('welcome');
        $response->assertSee('SocialNest');
        $response->assertSee('Make Your Shopify Products Easier to Share');
    }

    public function test_installed_and_authenticated_store_opening_via_shopify_admin_reaches_dashboard_directly(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'active-merchant.myshopify.com',
            'access_token' => 'shpat_active_entry_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $params = [
            'shop' => 'active-merchant.myshopify.com',
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvYWN0aXZlLW1lcmNoYW50',
            'timestamp' => (string) time(),
        ];
        $signedParams = $this->generateValidHmacParams($params);

        // Merchant launches app from Shopify Admin (hits App URL at root /)
        $response = $this->get('/?'.http_build_query($signedParams));

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.dashboard', [
            'shop' => 'active-merchant.myshopify.com',
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvYWN0aXZlLW1lcmNoYW50',
        ]));

        // Session was bound to the authenticated store
        $this->assertEquals('active-merchant.myshopify.com', session('shopify_shop'));
        $this->assertEquals($store->id, session('shopify_store_id'));

        // Follow redirect to dashboard
        $dashboardResponse = $this->get(route('shopify.dashboard', [
            'shop' => 'active-merchant.myshopify.com',
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvYWN0aXZlLW1lcmNoYW50',
        ]));

        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertViewIs('shopify.dashboard');
        $dashboardResponse->assertSee('active-merchant.myshopify.com');
        $dashboardResponse->assertSee('Live');
        $dashboardResponse->assertSee('Authenticated');
    }

    public function test_uninstalled_store_opening_via_shopify_admin_enters_installation_flow(): void
    {
        $params = [
            'shop' => 'fresh-store.myshopify.com',
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvZnJlc2gtc3RvcmU',
            'timestamp' => (string) time(),
        ];
        $signedParams = $this->generateValidHmacParams($params);

        $response = $this->get('/?'.http_build_query($signedParams));

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringStartsWith('https://fresh-store.myshopify.com/admin/oauth/authorize?', $location);

        // State nonce was generated and cached
        parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);
        $cached = Cache::get("shopify_oauth_state_{$queryParams['state']}");
        $this->assertNotNull($cached);
        $this->assertEquals('fresh-store.myshopify.com', $cached['shop']);
    }

    public function test_uninstalled_embedded_app_renders_iframe_breakout_script(): void
    {
        $params = [
            'shop' => 'embedded-store.myshopify.com',
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvZW1iZWRkZWQ=',
            'timestamp' => (string) time(),
            'embedded' => '1',
        ];
        $signedParams = $this->generateValidHmacParams($params);

        $response = $this->get('/?'.http_build_query($signedParams));

        $response->assertStatus(200);
        $response->assertSee('window.top.location.href');
        $response->assertSee('embedded-store.myshopify.com/admin/oauth/authorize');
    }

    public function test_invalid_or_tampered_launch_hmac_is_rejected(): void
    {
        $params = [
            'shop' => 'tampered-store.myshopify.com',
            'timestamp' => (string) time(),
            'hmac' => 'fake_tampered_hmac_signature',
        ];

        $response = $this->get('/?'.http_build_query($params));

        $response->assertStatus(403);
        $response->assertSee('Invalid Launch Signature');
    }

    public function test_malformed_or_invalid_shop_domain_is_rejected(): void
    {
        $response = $this->get('/?shop=malicious-domain.com&hmac=test');

        $response->assertStatus(400);
        $response->assertSee('Invalid Store Domain');
    }

    public function test_unauthenticated_request_cannot_access_dashboard_by_query_param_alone(): void
    {
        ShopifyStore::create([
            'shop' => 'victim-store.myshopify.com',
            'access_token' => 'shpat_victim_secret',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        // Attacker accesses /shopify/dashboard?shop=victim-store.myshopify.com with no session or HMAC
        $response = $this->get('/shopify/dashboard?shop=victim-store.myshopify.com');

        // Access is blocked; redirected to install
        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install', ['shop' => 'victim-store.myshopify.com']));
        $this->assertNull(session('shopify_shop'));
    }

    public function test_different_store_cannot_reuse_another_stores_authenticated_session(): void
    {
        $storeA = ShopifyStore::create([
            'shop' => 'store-a.myshopify.com',
            'access_token' => 'token_a',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $storeB = ShopifyStore::create([
            'shop' => 'store-b.myshopify.com',
            'access_token' => 'token_b',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        // Session belongs to Store A, but request specifies store B without HMAC
        $response = $this->withSession([
            'shopify_shop' => $storeA->shop,
            'shopify_store_id' => $storeA->id,
        ])->get('/shopify/dashboard?shop=store-b.myshopify.com');

        $response->assertStatus(200);
        // Dashboard resolves Store A, rejecting unauthenticated switch to Store B
        $response->assertSee('store-a.myshopify.com');
        $response->assertDontSee('store-b.myshopify.com');
    }

    public function test_shopify_admin_launch_with_valid_hmac_can_switch_to_installed_store(): void
    {
        $storeA = ShopifyStore::create([
            'shop' => 'store-a.myshopify.com',
            'access_token' => 'token_a',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $storeB = ShopifyStore::create([
            'shop' => 'store-b.myshopify.com',
            'access_token' => 'token_b',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $params = [
            'shop' => 'store-b.myshopify.com',
            'host' => 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvc3RvcmUtYg==',
            'timestamp' => (string) time(),
        ];
        $signedParams = $this->generateValidHmacParams($params);

        // Session was previously Store A, but Shopify sends a signed launch request for Store B
        $response = $this->withSession([
            'shopify_shop' => $storeA->shop,
            'shopify_store_id' => $storeA->id,
        ])->get('/shopify/dashboard?'.http_build_query($signedParams));

        $response->assertStatus(200);
        // Successfully verifies Store B from HMAC and renders Store B
        $response->assertSee('store-b.myshopify.com');
        $response->assertDontSee('store-a.myshopify.com');
        $this->assertEquals('store-b.myshopify.com', session('shopify_shop'));
    }

    public function test_incomplete_installation_cannot_access_dashboard(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'incomplete-store.myshopify.com',
            'access_token' => 'shpat_revoked_token',
            'scope' => 'read_products',
            'is_active' => false,
            'installed_at' => null,
            'uninstalled_at' => now()->subDay(),
        ]);

        $params = [
            'shop' => 'incomplete-store.myshopify.com',
            'timestamp' => (string) time(),
        ];
        $signedParams = $this->generateValidHmacParams($params);

        $response = $this->get('/?'.http_build_query($signedParams));

        // Incomplete installation redirected to OAuth authorize
        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://incomplete-store.myshopify.com/admin/oauth/authorize?', $location);
    }

    public function test_embedded_dashboard_contains_api_key_meta_tag_and_app_bridge_host_preservation(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'embedded-app.myshopify.com',
            'access_token' => 'shpat_active_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $host = 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvZW1iZWRkZWQtYXBw';

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => $host,
        ])->get(route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => $host,
        ]));

        $response->assertStatus(200);
        $response->assertSee('<meta name="shopify-api-key" content="'.$this->testApiKey.'" />', false);
        $response->assertSee('cdn.shopifycdn.net/shopifycloud/app-bridge.js');
        $response->assertSee('shopify_host');
        // Ensure sidebar links contain the host param
        $response->assertSee(e(route('shopify.social-links.index', ['host' => $host, 'shop' => $store->shop])), false);
    }

    public function test_secondary_pages_maintain_api_key_and_app_bridge_support(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'secondary-pages.myshopify.com',
            'access_token' => 'shpat_active_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $host = 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvc2Vjb25kYXJ5LXBhZ2Vz';

        $routesToTest = [
            route('shopify.social-links.index', ['shop' => $store->shop, 'host' => $host]),
            route('shopify.settings.index', ['shop' => $store->shop, 'host' => $host]),
            route('shopify.plans.index', ['shop' => $store->shop, 'host' => $host]),
            route('shopify.support.index', ['shop' => $store->shop, 'host' => $host]),
        ];

        foreach ($routesToTest as $testUrl) {
            $response = $this->withSession([
                'shopify_shop' => $store->shop,
                'shopify_store_id' => $store->id,
                'shopify_host' => $host,
            ])->get($testUrl);

            $response->assertStatus(200);
            $response->assertSee('<meta name="shopify-api-key" content="'.$this->testApiKey.'" />', false);
            $response->assertSee('cdn.shopifycdn.net/shopifycloud/app-bridge.js');
        }
    }

    public function test_submitting_social_link_preserves_host_parameter_on_redirect(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'links-redirect.myshopify.com',
            'access_token' => 'shpat_active_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $host = 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvbGlua3MtcmVkaXJlY3Q=';

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => $host,
        ])->post(route('shopify.social-links.store'), [
            'platform' => 'instagram',
            'url' => 'https://instagram.com/mybrand',
            'label' => 'Instagram Store',
            'host' => $host,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.social-links.index', [
            'host' => $host,
        ]));
    }

    public function test_updating_storefront_settings_preserves_host_parameter_on_redirect(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'settings-redirect.myshopify.com',
            'access_token' => 'shpat_active_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $host = 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvc2V0dGluZ3MtcmVkaXJlY3Q=';

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => $host,
        ])->put(route('shopify.settings.update'), [
            'position' => 'bottom_left',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'is_enabled' => true,
            'host' => $host,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.settings.index', [
            'host' => $host,
        ]));
    }

    public function test_direct_dashboard_access_without_session_or_params_redirects_to_install_without_redirect_loop(): void
    {
        $response = $this->get('/shopify/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install'));

        // Follow redirect to ensure no redirect loop
        $installResponse = $this->get(route('shopify.install'));
        $installResponse->assertStatus(200);
        $installResponse->assertViewIs('shopify.install');
    }

    public function test_all_merchant_routes_reject_direct_unauthenticated_access(): void
    {
        $routes = [
            route('shopify.dashboard'),
            route('shopify.social-links.index'),
            route('shopify.social-links.create'),
            route('shopify.settings.index'),
            route('shopify.plans.index'),
            route('shopify.support.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertStatus(302);
            $response->assertRedirect(route('shopify.install'));
        }
    }

    public function test_uninstalled_or_inactive_store_in_session_is_cleared_and_redirected_to_install(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'uninstalled-merchant.myshopify.com',
            'access_token' => 'shpat_uninstalled_token',
            'scope' => 'read_products',
            'is_active' => false,
            'installed_at' => now()->subMonth(),
            'uninstalled_at' => now()->subDay(),
        ]);

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ])->get('/shopify/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install', ['shop' => 'uninstalled-merchant.myshopify.com']));

        // Session was cleared of the invalid store
        $this->assertNull(session('shopify_shop'));
        $this->assertNull(session('shopify_store_id'));
    }

    public function test_valid_authenticated_store_can_access_all_merchant_routes(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'merchant-all-routes.myshopify.com',
            'access_token' => 'shpat_active_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $host = 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvbWVyY2hhbnQtYWxsLXJvdXRlcw==';

        $routesWithViews = [
            route('shopify.dashboard', ['shop' => $store->shop, 'host' => $host]) => 'shopify.dashboard',
            route('shopify.social-links.index', ['shop' => $store->shop, 'host' => $host]) => 'shopify.social-links.index',
            route('shopify.social-links.create', ['shop' => $store->shop, 'host' => $host]) => 'shopify.social-links.create',
            route('shopify.settings.index', ['shop' => $store->shop, 'host' => $host]) => 'shopify.settings.index',
            route('shopify.plans.index', ['shop' => $store->shop, 'host' => $host]) => 'shopify.plans.index',
            route('shopify.support.index', ['shop' => $store->shop, 'host' => $host]) => 'shopify.support.index',
        ];

        foreach ($routesWithViews as $url => $viewName) {
            $response = $this->withSession([
                'shopify_shop' => $store->shop,
                'shopify_store_id' => $store->id,
                'shopify_host' => $host,
            ])->get($url);

            $response->assertStatus(200);
            $response->assertViewIs($viewName);
            $response->assertSee('merchant-all-routes.myshopify.com');
            $response->assertSee('<meta name="shopify-api-key" content="'.$this->testApiKey.'" />', false);
        }
    }

    public function test_shopify_service_generates_canonical_admin_app_url(): void
    {
        /** @var ShopifyService $service */
        $service = app(ShopifyService::class);

        // From host (base64 of admin.shopify.com/store/my-brand)
        $host = base64_encode('admin.shopify.com/store/my-brand');
        $adminUrlFromHost = $service->getAdminAppUrl('my-brand.myshopify.com', $host);
        $this->assertEquals("https://admin.shopify.com/store/my-brand/apps/{$this->testApiKey}", $adminUrlFromHost);

        // From shop domain alone
        $adminUrlFromShop = $service->getAdminAppUrl('another-store.myshopify.com');
        $this->assertEquals("https://admin.shopify.com/store/another-store/apps/{$this->testApiKey}", $adminUrlFromShop);
    }

    public function test_embedded_layout_contains_auto_embedding_break_in_script_and_app_bridge_cdn(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'embed-check.myshopify.com',
            'access_token' => 'shpat_embed_check_token',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ])->get(route('shopify.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('window.top === window.self', false);
        $response->assertSee('cdn.shopifycdn.net/shopifycloud/app-bridge.js', false);
    }

    public function test_missing_session_state_redirects_to_install_without_infinite_loop(): void
    {
        // Unauthenticated request directly hitting /shopify/dashboard with shop query parameter
        $response = $this->get(route('shopify.dashboard', ['shop' => 'darkcypher.myshopify.com']));

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install', ['shop' => 'darkcypher.myshopify.com']));

        // Following to install does NOT bounce back to dashboard in a loop if session is missing;
        // instead, it begins the controlled OAuth authorization flow
        $installResponse = $this->get(route('shopify.install', ['shop' => 'darkcypher.myshopify.com']));
        $installResponse->assertStatus(302);
        $location = $installResponse->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringStartsWith('https://darkcypher.myshopify.com/admin/oauth/authorize?', $location);
    }

    public function test_redirect_loop_safeguard_halts_repeated_identical_redirects(): void
    {
        // Simulate repeated attempts exceeding threshold
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get(route('shopify.dashboard', ['shop' => 'loop-check.myshopify.com']));
            $response->assertStatus(302);
        }

        // 4th attempt triggers loop-prevention safeguard
        $loopResponse = $this->get(route('shopify.dashboard', ['shop' => 'loop-check.myshopify.com']));
        $loopResponse->assertStatus(429);
        $loopResponse->assertViewIs('shopify.error');
        $loopResponse->assertSee('Authentication Redirect Loop Prevented');
        $loopResponse->assertSee('Re-authenticate with Shopify');
    }
}
