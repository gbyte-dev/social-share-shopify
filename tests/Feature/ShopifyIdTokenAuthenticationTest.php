<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\SocialLink;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ShopifyIdTokenAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected string $testApiKey = 'test_shopify_api_key_123';

    protected string $testApiSecret = 'test_shopify_api_secret_32bytes_minimum_length_key_abc123';

    protected ShopifyStore $store;

    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shopify.api_key', $this->testApiKey);
        Config::set('shopify.api_secret', $this->testApiSecret);
        Config::set('shopify.webhook_secret', $this->testApiSecret);

        $this->store = ShopifyStore::create([
            'shop' => '26-sep.myshopify.com',
            'access_token' => 'shpua_test_access_token_123',
            'scope' => 'read_products,write_content,read_themes,write_themes',
            'is_active' => true,
            'installed_at' => now(),
            'plan_name' => 'premium',
            'plan_status' => 'active',
            'plan_activated_at' => now(),
        ]);

        $this->proPlan = Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'SocialNest Pro',
                'price' => 5.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'description' => 'Complete storefront engagement suite',
                'features' => ['All social platforms', 'Custom color picker', 'Analytics'],
                'is_active' => true,
            ]
        );
    }

    /**
     * Helper to generate a signed Shopify session ID token.
     *
     * @param  array<string, mixed>  $customClaims
     */
    protected function generateSessionToken(array $customClaims = [], ?string $secret = null): string
    {
        $payload = array_merge([
            'iss' => 'https://26-sep.myshopify.com/admin',
            'dest' => 'https://26-sep.myshopify.com',
            'aud' => $this->testApiKey,
            'sub' => '12345678',
            'exp' => time() + 60,
            'nbf' => time() - 10,
            'iat' => time() - 10,
            'jti' => 'test-jwt-id-'.uniqid(),
            'sid' => 'test-session-id-'.uniqid(),
        ], $customClaims);

        return JWT::encode($payload, $secret ?? $this->testApiSecret, 'HS256');
    }

    public function test_valid_session_token_succeeds_and_resolves_correct_store(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'total_clicks',
                'clicks_today',
            ],
        ]);
    }

    public function test_valid_session_token_with_unified_admin_issuer_succeeds(): void
    {
        $token = $this->generateSessionToken([
            'iss' => 'https://admin.shopify.com/store/26-sep',
            'dest' => 'https://26-sep.myshopify.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(200);
    }

    public function test_invalid_signature_is_rejected_with_401_and_retry_header(): void
    {
        $token = $this->generateSessionToken([], 'wrong_signing_secret_32bytes_minimum_length_key_xyz987');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid Shopify session token signature.',
        ]);
    }

    public function test_expired_token_is_rejected_with_401_and_retry_header(): void
    {
        // Expired past the 10-second leeway
        $token = $this->generateSessionToken([
            'exp' => time() - 30,
            'nbf' => time() - 90,
            'iat' => time() - 90,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shopify session token has expired.',
        ]);
    }

    public function test_future_nbf_token_is_rejected_with_401_and_retry_header(): void
    {
        // Token not valid for another 60 seconds (beyond leeway)
        $token = $this->generateSessionToken([
            'nbf' => time() + 60,
            'exp' => time() + 120,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shopify session token is not yet valid.',
        ]);
    }

    public function test_wrong_audience_is_rejected_with_401_and_retry_header(): void
    {
        $token = $this->generateSessionToken([
            'aud' => 'different_client_id_789',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Session token audience mismatch.',
        ]);
    }

    public function test_invalid_destination_domain_is_rejected_with_401_and_retry_header(): void
    {
        $token = $this->generateSessionToken([
            'dest' => 'https://malicious-external-domain.com',
            'iss' => 'https://malicious-external-domain.com/admin',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Destination domain is not a valid myshopify.com store.',
        ]);
    }

    public function test_mismatched_issuer_and_destination_is_rejected_with_401_and_retry_header(): void
    {
        $token = $this->generateSessionToken([
            'iss' => 'https://admin.shopify.com/store/attacker-store',
            'dest' => 'https://26-sep.myshopify.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Issuer handle does not match destination store handle.',
        ]);
    }

    public function test_unknown_or_uninstalled_shop_is_rejected_with_401_and_retry_header(): void
    {
        $token = $this->generateSessionToken([
            'iss' => 'https://unknown-store.myshopify.com/admin',
            'dest' => 'https://unknown-store.myshopify.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Store not installed or inactive.',
        ]);
    }

    public function test_shop_parameter_spoofing_is_rejected(): void
    {
        // Token is issued for 26-sep.myshopify.com, but query specifies victim-store.myshopify.com
        $token = $this->generateSessionToken([
            'iss' => 'https://26-sep.myshopify.com/admin',
            'dest' => 'https://26-sep.myshopify.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/analytics?shop=victim-store.myshopify.com');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shop parameter does not match authenticated session token.',
        ]);
    }

    public function test_missing_authorization_header_on_protected_endpoints_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_cross_shop_data_isolation(): void
    {
        // Store B
        $storeB = ShopifyStore::create([
            'shop' => 'store-b.myshopify.com',
            'access_token' => 'shpua_store_b_token_456',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
            'plan_name' => 'premium',
            'plan_status' => 'active',
            'plan_activated_at' => now(),
        ]);

        $linkB = SocialLink::create([
            'shopify_store_id' => $storeB->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/storeb',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Token is for Store A (26-sep)
        $tokenA = $this->generateSessionToken([
            'iss' => 'https://26-sep.myshopify.com/admin',
            'dest' => 'https://26-sep.myshopify.com',
        ]);

        // Attempt to delete Store B's link using Store A's authenticated session
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'Accept' => 'application/json',
        ])->delete("/shopify/social-links/{$linkB->id}");

        // Must fail with 404 since linkB does not belong to Store A
        $response->assertStatus(404);

        // Verify Store B's link still exists unharmed
        $this->assertDatabaseHas('social_links', [
            'id' => $linkB->id,
            'shopify_store_id' => $storeB->id,
        ]);
    }

    public function test_top_level_page_views_maintain_session_hmac_compatibility_without_redirect_loop(): void
    {
        $params = [
            'shop' => $this->store->shop,
            'host' => base64_encode('admin.shopify.com/store/26-sep'),
            'timestamp' => (string) time(),
        ];
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = "{$k}={$v}";
        }
        $params['hmac'] = hash_hmac('sha256', implode('&', $pairs), $this->testApiSecret);

        $response = $this->get('/shopify/dashboard?'.http_build_query($params));

        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');
    }

    public function test_oauth_callback_continues_to_function_without_token_requirement(): void
    {
        // Unauthenticated OAuth callback with missing or invalid parameters returns 400 or redirect, not 401 JWT rejection
        $response = $this->get('/shopify/callback');

        // Without HMAC and shop, callback rejects with 400 or redirect to install, verifying it is exempt from JWT middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_shopify_webhook_and_proxy_routes_remain_unaffected(): void
    {
        // Shopify webhook endpoint does not require ID token (it expects Shopify HMAC)
        $webhookPayload = json_encode(['id' => 12345]);
        $hmac = base64_encode(hash_hmac('sha256', $webhookPayload, $this->testApiSecret, true));

        $headers = [
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Topic' => 'app/uninstalled',
            'X-Shopify-Shop-Domain' => '26-sep.myshopify.com',
            'Content-Type' => 'application/json',
        ];

        $response = $this->call(
            'POST',
            '/shopify/webhooks',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $webhookPayload
        );

        // Status must be 200 (processed) and NOT 401 unauthorized
        $response->assertStatus(200);
    }

    public function test_authenticated_page_navigation_to_settings_with_id_token_succeeds_without_session_cookie(): void
    {
        $token = $this->generateSessionToken();

        // Make request with Authorization Bearer token, X-Shopify-Navigation header, and NO session cookies
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/settings');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.settings.index');
        $response->assertSee('Storefront Settings');
        $response->assertSee('Product Page Share Button');
    }

    public function test_authenticated_page_navigation_to_settings_missing_token_returns_401_with_retry_header(): void
    {
        // Internal navigation request without Bearer token must be rejected with 401 and retry header
        $response = $this->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/settings');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_authenticated_page_navigation_to_settings_with_expired_token_returns_401_with_retry_header(): void
    {
        // Expired token beyond leeway
        $token = $this->generateSessionToken([
            'exp' => time() - 30,
            'nbf' => time() - 90,
            'iat' => time() - 90,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/settings');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shopify session token has expired.',
        ]);
    }

    public function test_authenticated_page_navigation_to_dashboard_with_id_token_succeeds_without_session_cookie(): void
    {
        $token = $this->generateSessionToken();

        // Make request to dashboard with Authorization Bearer token, X-Shopify-Navigation header, and NO session cookies
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');
        $response->assertSee('Store overview, social engagement, and storefront feature controls');
        $response->assertSee('Click Analytics');
    }

    public function test_repeated_back_and_forth_navigation_transitions_succeed_independently(): void
    {
        // Simulate repeated transitions: Dashboard -> Settings -> Dashboard -> Settings -> Dashboard
        for ($i = 0; $i < 3; $i++) {
            $tokenSettings = $this->generateSessionToken();
            $settingsResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenSettings}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get('/shopify/settings');

            $settingsResponse->assertStatus(200);
            $settingsResponse->assertViewIs('shopify.settings.index');

            $tokenDashboard = $this->generateSessionToken();
            $dashboardResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenDashboard}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get('/shopify/dashboard');

            $dashboardResponse->assertStatus(200);
            $dashboardResponse->assertViewIs('shopify.dashboard');
        }
    }

    public function test_authenticated_page_navigation_with_invalid_signature_returns_401_with_retry_header(): void
    {
        $token = $this->generateSessionToken([], 'invalid_signing_secret_32bytes_minimum_length_key_abc789');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/settings');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid Shopify session token signature.',
        ]);
    }

    public function test_missing_bearer_with_valid_session_and_navigation_header_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/settings');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_missing_bearer_with_valid_session_and_ajax_header_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->get('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_missing_bearer_with_valid_session_and_json_request_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->getJson('/shopify/analytics');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_invalid_bearer_with_valid_session_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'Authorization' => 'Bearer completely.invalid.jwt.token',
            'X-Shopify-Navigation' => '1',
            'Accept' => 'text/html',
        ])->get('/shopify/dashboard');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
    }

    public function test_valid_bearer_with_no_session_succeeds_200(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Shopify-Navigation' => '1',
            'Accept' => 'text/html, application/xhtml+xml',
        ])->get('/shopify/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');
    }

    public function test_native_document_request_with_matching_session_shop_succeeds(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->get("/shopify/dashboard?shop={$this->store->shop}");

        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');
    }

    public function test_native_document_request_with_mismatched_session_shop_is_rejected_with_403(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->get('/shopify/dashboard?shop=attacker-store.myshopify.com');

        $response->assertStatus(403);
        $response->assertViewIs('shopify.error');
    }

    public function test_valid_initial_hmac_launch_preserves_existing_behavior(): void
    {
        $params = [
            'shop' => $this->store->shop,
            'host' => base64_encode('admin.shopify.com/store/26-sep'),
            'timestamp' => (string) time(),
        ];
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = "{$k}={$v}";
        }
        $params['hmac'] = hash_hmac('sha256', implode('&', $pairs), $this->testApiSecret);

        $response = $this->get('/shopify/dashboard?'.http_build_query($params));

        $response->assertStatus(200);
        $response->assertViewIs('shopify.dashboard');
    }

    public function test_expired_bearer_token_returns_401_with_retry_header(): void
    {
        $token = $this->generateSessionToken([
            'exp' => time() - 30,
            'nbf' => time() - 90,
            'iat' => time() - 90,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Shopify-Navigation' => '1',
            'Accept' => 'text/html, application/xhtml+xml',
        ])->get('/shopify/settings');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shopify session token has expired.',
        ]);
    }

    public function test_invalid_signature_returns_401_with_retry_header(): void
    {
        $token = $this->generateSessionToken([], 'invalid_signing_secret_32bytes_minimum_length_key_abc789');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Shopify-Navigation' => '1',
            'Accept' => 'text/html, application/xhtml+xml',
        ])->get('/shopify/settings');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid Shopify session token signature.',
        ]);
    }

    public function test_authenticated_page_navigation_to_social_links_with_id_token_succeeds_without_session_cookie(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/social-links');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.social-links.index');
        $response->assertSee('Social Links');
    }

    public function test_authenticated_page_navigation_to_social_links_missing_bearer_token_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/social-links');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_authenticated_page_navigation_to_social_links_invalid_bearer_token_returns_401(): void
    {
        $token = $this->generateSessionToken([], 'invalid_signing_secret_32bytes_minimum_length_key_abc789');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/social-links');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid Shopify session token signature.',
        ]);
    }

    public function test_repeated_dashboard_social_links_dashboard_navigation_transitions_succeed(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $tokenLinks = $this->generateSessionToken();
            $linksResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenLinks}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get('/shopify/social-links');

            $linksResponse->assertStatus(200);
            $linksResponse->assertViewIs('shopify.social-links.index');

            $tokenDashboard = $this->generateSessionToken();
            $dashboardResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenDashboard}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get('/shopify/dashboard');

            $dashboardResponse->assertStatus(200);
            $dashboardResponse->assertViewIs('shopify.dashboard');
        }
    }

    public function test_authenticated_page_navigation_to_social_links_edit_route_preserves_parameters(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->store->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/myboutique',
            'label' => 'Instagram Storefront',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get("/shopify/social-links/{$link->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('shopify.social-links.edit');
        $response->assertSee('Edit Instagram Link');
        $response->assertSee('https://instagram.com/myboutique');
    }

    public function test_authenticated_page_navigation_to_social_links_edit_cross_shop_access_is_rejected(): void
    {
        $otherStore = ShopifyStore::create([
            'shop' => 'victim-other-store.myshopify.com',
            'access_token' => 'shpua_other_access_token_123',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $otherLink = SocialLink::create([
            'shopify_store_id' => $otherStore->id,
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/123456789',
            'label' => 'Secret WhatsApp',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Request with Store A's valid token attempting to access Store B's link ID
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get("/shopify/social-links/{$otherLink->id}/edit");

        // Must be rejected with 404 (Resource not found for this store)
        $response->assertStatus(404);
    }

    public function test_authenticated_page_navigation_to_plans_with_id_token_succeeds_without_session_cookie(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.plans.index');
        $response->assertSee('Subscription & Billing');
        $response->assertSee('SocialNest Pro');
    }

    public function test_authenticated_page_navigation_to_plans_missing_bearer_token_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/plans');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_authenticated_page_navigation_to_plans_invalid_bearer_token_returns_401(): void
    {
        $token = $this->generateSessionToken([], 'invalid_signing_secret_32bytes_minimum_length_key_abc789');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/plans');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid Shopify session token signature.',
        ]);
    }

    public function test_authenticated_page_navigation_to_plans_checkout_route_preserves_parameters(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get("/shopify/plans/checkout/{$this->proPlan->id}");

        $response->assertStatus(200);
        $response->assertViewIs('shopify.plans.checkout');
        $response->assertSee('Confirm Dummy Subscription');
        $response->assertSee('SocialNest Pro');
    }

    public function test_repeated_dashboard_plans_checkout_plans_dashboard_navigation_transitions_succeed(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $tokenPlans = $this->generateSessionToken();
            $plansResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenPlans}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get('/shopify/plans');

            $plansResponse->assertStatus(200);
            $plansResponse->assertViewIs('shopify.plans.index');

            $tokenCheckout = $this->generateSessionToken();
            $checkoutResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenCheckout}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get("/shopify/plans/checkout/{$this->proPlan->id}");

            $checkoutResponse->assertStatus(200);
            $checkoutResponse->assertViewIs('shopify.plans.checkout');

            $tokenDashboard = $this->generateSessionToken();
            $dashboardResponse = $this->withHeaders([
                'Authorization' => "Bearer {$tokenDashboard}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get('/shopify/dashboard');

            $dashboardResponse->assertStatus(200);
            $dashboardResponse->assertViewIs('shopify.dashboard');
        }
    }

    public function test_plans_status_api_endpoint_with_id_token_returns_subscription_status(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get('/shopify/plans/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'shop',
            'is_active',
            'status',
            'plan',
        ]);
        $response->assertJson([
            'shop' => $this->store->shop,
        ]);
    }

    public function test_plans_status_api_endpoint_missing_bearer_token_returns_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/shopify/plans/status');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
    }

    public function test_authenticated_page_navigation_to_support_with_id_token_succeeds_without_session_cookie(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.support.index');
        $response->assertSee('Help & Support');
        $response->assertSee('Storefront Theme Activation Guide');
        $response->assertSee('How to Set Up Wishlist');
        $response->assertSee('Automated 1-Click Wishlist Setup');
        $response->assertSee('btn-auto-setup-wishlist');
    }

    public function test_authenticated_page_navigation_to_support_missing_bearer_token_returns_401(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Missing or malformed Authorization Bearer session token.',
        ]);
    }

    public function test_authenticated_page_navigation_to_support_invalid_bearer_token_returns_401(): void
    {
        $token = $this->generateSessionToken([], 'invalid_signing_secret_32bytes_minimum_length_key_abc789');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid Shopify session token signature.',
        ]);
    }

    public function test_authenticated_page_navigation_to_support_with_expired_token_returns_401(): void
    {
        $token = $this->generateSessionToken([
            'exp' => time() - 3600,
            'nbf' => time() - 7200,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shopify session token has expired.',
        ]);
    }

    public function test_authenticated_page_navigation_to_support_cross_shop_access_is_rejected(): void
    {
        $token = $this->generateSessionToken();

        // Token belongs to $this->store, but query explicitly requests attacker-shop
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support?shop=attacker-shop.myshopify.com');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Shop parameter does not match authenticated session token.',
        ]);
    }

    public function test_full_navigation_cycle_across_all_embedded_pages_including_support(): void
    {
        $pages = [
            ['url' => '/shopify/dashboard', 'view' => 'shopify.dashboard', 'title' => 'SocialNest'],
            ['url' => '/shopify/settings', 'view' => 'shopify.settings.index', 'title' => 'Storefront Settings'],
            ['url' => '/shopify/social-links', 'view' => 'shopify.social-links.index', 'title' => 'Social Links'],
            ['url' => '/shopify/plans', 'view' => 'shopify.plans.index', 'title' => 'Subscription & Billing'],
            ['url' => '/shopify/support', 'view' => 'shopify.support.index', 'title' => 'Help & Support'],
            ['url' => '/shopify/dashboard', 'view' => 'shopify.dashboard', 'title' => 'SocialNest'],
        ];

        foreach ($pages as $page) {
            $freshToken = $this->generateSessionToken();
            $response = $this->withHeaders([
                'Authorization' => "Bearer {$freshToken}",
                'Accept' => 'text/html, application/xhtml+xml',
                'X-Shopify-Navigation' => '1',
            ])->get($page['url']);

            $response->assertStatus(200);
            $response->assertViewIs($page['view']);
            $response->assertSee($page['title']);
        }
    }
}
