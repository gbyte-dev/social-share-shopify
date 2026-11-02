<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyInstallationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected string $testApiKey = 'test_shopify_api_key_123';

    protected string $testApiSecret = 'test_shopify_api_secret_456';

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
     * Helper to compute Shopify OAuth callback query string and valid HMAC.
     */
    protected function generateValidCallbackParams(array $params): array
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

    public function test_it_renders_installation_entry_page_when_no_shop_parameter_provided(): void
    {
        $response = $this->get('/shopify/install');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.install');
        $response->assertSee('Connect SocialNest');
    }

    public function test_it_validates_and_rejects_invalid_or_malformed_shop_domains(): void
    {
        $invalidDomains = [
            'attacker.com',
            'evil.myshopify.com.attacker.com',
            'bad/domain.myshopify.com',
            '---.myshopify.com',
            'javascript:alert(1)',
        ];

        foreach ($invalidDomains as $invalidDomain) {
            $response = $this->get('/shopify/install?shop='.urlencode($invalidDomain));
            $response->assertRedirect(route('shopify.install'));
            $response->assertSessionHas('error');
        }
    }

    public function test_it_rejects_installation_when_shopify_credentials_are_missing(): void
    {
        Config::set('shopify.api_key', null);

        $response = $this->get('/shopify/install?shop=mystore.myshopify.com');

        $response->assertStatus(500);
        $response->assertSee('Shopify API credentials missing');
    }

    public function test_it_starts_oauth_and_redirects_to_shopify_with_valid_shop_domain(): void
    {
        $response = $this->get('/shopify/install?shop=test-boutique.myshopify.com');

        $response->assertStatus(302);

        $targetUrl = $response->headers->get('Location');
        $this->assertNotNull($targetUrl);
        $this->assertStringStartsWith('https://test-boutique.myshopify.com/admin/oauth/authorize?', $targetUrl);

        parse_str(parse_url($targetUrl, PHP_URL_QUERY), $queryParams);
        $this->assertEquals($this->testApiKey, $queryParams['client_id']);
        $this->assertEquals('read_products', $queryParams['scope']);
        $this->assertEquals($this->testRedirectUri, $queryParams['redirect_uri']);
        $this->assertNotEmpty($queryParams['state']);

        // Verify state is cached
        $cached = Cache::get("shopify_oauth_state_{$queryParams['state']}");
        $this->assertNotNull($cached);
        $this->assertEquals('test-boutique.myshopify.com', $cached['shop']);
    }

    public function test_it_renders_iframe_breakout_script_when_embedded(): void
    {
        $response = $this->get('/shopify/install?shop=test-boutique.myshopify.com&embedded=1');

        $response->assertStatus(200);
        $response->assertSee('window.top.location.href');
        $response->assertSee('test-boutique.myshopify.com/admin/oauth/authorize');
    }

    public function test_already_installed_valid_store_redirects_to_dashboard_without_duplicate(): void
    {
        ShopifyStore::create([
            'shop' => 'already-active.myshopify.com',
            'access_token' => 'shpat_active_123',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $response = $this->get('/shopify/install?shop=already-active.myshopify.com');

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.dashboard', ['shop' => 'already-active.myshopify.com']));
        $this->assertEquals(1, ShopifyStore::where('shop', 'already-active.myshopify.com')->count());
    }

    public function test_it_rejects_callback_with_missing_parameters(): void
    {
        $response = $this->get('/shopify/callback');

        $response->assertStatus(400);
        $response->assertSee('Missing Parameters');
    }

    public function test_it_rejects_callback_with_invalid_or_forged_hmac_signature(): void
    {
        $params = [
            'code' => 'fake_auth_code_123',
            'shop' => 'test-boutique.myshopify.com',
            'state' => 'some_random_state',
            'timestamp' => time(),
            'hmac' => 'forged_invalid_hmac_hash',
        ];

        $response = $this->get('/shopify/callback?'.http_build_query($params));

        $response->assertStatus(400);
        $response->assertSee('Verification Failed');
    }

    public function test_it_rejects_callback_with_invalid_or_expired_state_nonce(): void
    {
        $params = $this->generateValidCallbackParams([
            'code' => 'valid_auth_code_123',
            'shop' => 'test-boutique.myshopify.com',
            'state' => 'expired_state_not_in_cache',
            'timestamp' => time(),
        ]);

        $response = $this->get('/shopify/callback?'.http_build_query($params));

        $response->assertStatus(400);
        $response->assertSee('Invalid Session State');
    }

    public function test_it_handles_token_exchange_failure_gracefully(): void
    {
        $state = 'valid_active_state_nonce';
        Cache::put("shopify_oauth_state_{$state}", [
            'shop' => 'test-boutique.myshopify.com',
            'host' => 'dGVzdC1ib3V0aXF1ZS5teXNob3BpZnkuY29tL2FkbWlu',
        ], now()->addMinutes(15));

        Http::fake([
            'https://test-boutique.myshopify.com/admin/oauth/access_token' => Http::response([
                'error' => 'invalid_request',
                'error_description' => 'Authorization code was not found or was already used',
            ], 400),
        ]);

        $params = $this->generateValidCallbackParams([
            'code' => 'expired_code_123',
            'shop' => 'test-boutique.myshopify.com',
            'state' => $state,
            'timestamp' => time(),
        ]);

        $response = $this->get('/shopify/callback?'.http_build_query($params));

        $response->assertStatus(502);
        $response->assertSee('Token Exchange Failed');
    }

    public function test_it_successfully_completes_installation_and_redirects_to_dashboard(): void
    {
        $state = 'valid_oauth_state_123';
        $host = 'dGVzdC1ib3V0aXF1ZS5teXNob3BpZnkuY29tL2FkbWlu';

        Cache::put("shopify_oauth_state_{$state}", [
            'shop' => 'test-boutique.myshopify.com',
            'host' => $host,
        ], now()->addMinutes(15));

        Http::fake([
            'https://test-boutique.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'shpat_test_permanent_token_999',
                'scope' => 'read_products',
            ], 200),
        ]);

        $params = $this->generateValidCallbackParams([
            'code' => 'valid_auth_code_789',
            'shop' => 'test-boutique.myshopify.com',
            'state' => $state,
            'timestamp' => time(),
            'host' => $host,
        ]);

        $response = $this->get('/shopify/callback?'.http_build_query($params));

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.dashboard', [
            'shop' => 'test-boutique.myshopify.com',
            'host' => $host,
        ]));

        // Assert database record exists
        $store = ShopifyStore::where('shop', 'test-boutique.myshopify.com')->first();
        $this->assertNotNull($store);
        $this->assertTrue($store->is_active);
        $this->assertEquals('read_products', $store->scope);

        // Assert token is encrypted at rest in the database
        $rawDatabaseToken = $store->getRawOriginal('access_token');
        $this->assertNotEquals('shpat_test_permanent_token_999', $rawDatabaseToken);
        $this->assertEquals('shpat_test_permanent_token_999', $store->access_token);

        // Assert session is established
        $this->assertEquals('test-boutique.myshopify.com', session('shopify_shop'));
        $this->assertEquals($store->id, session('shopify_store_id'));
    }

    public function test_repeated_installation_attempts_are_idempotent(): void
    {
        // Pre-create store
        $existingStore = ShopifyStore::create([
            'shop' => 'repeat-boutique.myshopify.com',
            'access_token' => 'old_token_111',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now()->subDays(10),
        ]);

        $state = 'reauth_state_456';
        Cache::put("shopify_oauth_state_{$state}", [
            'shop' => 'repeat-boutique.myshopify.com',
            'host' => null,
        ], now()->addMinutes(15));

        Http::fake([
            'https://repeat-boutique.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'new_fresh_token_222',
                'scope' => 'read_products',
            ], 200),
        ]);

        $params = $this->generateValidCallbackParams([
            'code' => 'new_code_456',
            'shop' => 'repeat-boutique.myshopify.com',
            'state' => $state,
            'timestamp' => time(),
        ]);

        $response = $this->get('/shopify/callback?'.http_build_query($params));

        $response->assertStatus(302);

        // Total count must still be 1 (idempotent, no duplicates)
        $this->assertEquals(1, ShopifyStore::where('shop', 'repeat-boutique.myshopify.com')->count());

        $updatedStore = ShopifyStore::where('shop', 'repeat-boutique.myshopify.com')->first();
        $this->assertEquals($existingStore->id, $updatedStore->id);
        $this->assertEquals('new_fresh_token_222', $updatedStore->access_token);
    }

    public function test_unauthenticated_dashboard_access_is_blocked_and_redirects_to_install(): void
    {
        $response = $this->get('/shopify/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install'));
    }

    public function test_uninstalled_store_cannot_access_dashboard(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'uninstalled-store.myshopify.com',
            'access_token' => 'token_xyz',
            'scope' => 'read_products',
            'is_active' => false,
            'installed_at' => now()->subMonths(1),
            'uninstalled_at' => now()->subDay(),
        ]);

        $response = $this->withSession(['shopify_shop' => $store->shop])
            ->get('/shopify/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install', ['shop' => $store->shop]));
    }

    public function test_authenticated_store_can_access_dashboard(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'active-boutique.myshopify.com',
            'access_token' => 'valid_token_333',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $response = $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ])->get('/shopify/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');
        $response->assertSee('active-boutique.myshopify.com');
        $response->assertSee('Live');
        $response->assertSee('Authenticated');

        // Verify CSP frame-ancestors header
        $response->assertHeader('Content-Security-Policy', 'frame-ancestors https://active-boutique.myshopify.com https://admin.shopify.com;');
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }

    public function test_store_isolation_resolves_only_authenticated_store(): void
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

        // Attacker attempts to access Store B while logged into Store A
        $response = $this->withSession([
            'shopify_shop' => $storeA->shop,
            'shopify_store_id' => $storeA->id,
        ])->get('/shopify/dashboard?shop=store-b.myshopify.com');

        $response->assertStatus(200);
        // Dashboard renders for Store A, rejecting cross-store switch without authenticating
        $response->assertSee('store-a.myshopify.com');
        $response->assertDontSee('store-b.myshopify.com');
    }

    public function test_webhook_rejects_requests_with_invalid_hmac(): void
    {
        $payload = json_encode(['id' => 12345]);
        $headers = [
            'X-Shopify-Topic' => 'app/uninstalled',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Hmac-Sha256' => 'invalid_forged_webhook_hmac',
        ];

        $response = $this->call(
            'POST',
            '/shopify/webhooks',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $payload
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid HMAC signature']);
    }

    public function test_webhook_handles_app_uninstalled_idempotently(): void
    {
        $store = ShopifyStore::create([
            'shop' => 'store-to-uninstall.myshopify.com',
            'access_token' => 'token_uninstall',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $payload = json_encode(['id' => 999999, 'domain' => 'store-to-uninstall.myshopify.com']);
        $validWebhookHmac = base64_encode(hash_hmac('sha256', $payload, $this->testApiSecret, true));

        $headers = [
            'X-Shopify-Topic' => 'app/uninstalled',
            'X-Shopify-Shop-Domain' => 'store-to-uninstall.myshopify.com',
            'X-Shopify-Hmac-Sha256' => $validWebhookHmac,
        ];

        $response = $this->call(
            'POST',
            '/shopify/webhooks',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $payload
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $store->refresh();
        $this->assertFalse($store->is_active);
        $this->assertNotNull($store->uninstalled_at);

        // Repeated delivery should execute idempotently without errors
        $repeatResponse = $this->call(
            'POST',
            '/shopify/webhooks',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $payload
        );

        $repeatResponse->assertStatus(200);
    }
}
