<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupportPageTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected Plan $proPlan;

    protected string $testApiKey = 'test_shopify_api_key_123';

    protected string $testApiSecret = 'test_shopify_api_secret_456_minimum_32_bytes_length';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('shopify.api_key', $this->testApiKey);
        Config::set('shopify.api_secret', $this->testApiSecret);
        Config::set('shopify.app_url', 'https://socialnest.test');

        $this->store = ShopifyStore::create([
            'shop' => 'support-merchant.myshopify.com',
            'access_token' => 'shpat_support_test_token',
            'scope' => 'read_products,write_products,read_themes,write_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->proPlan = Plan::create([
            'name' => 'SocialNest Pro',
            'slug' => 'socialnest-pro',
            'price' => 5.00,
            'interval' => 'EVERY_30_DAYS',
            'features' => ['All 20+ social platforms', 'Custom position control'],
            'is_active' => true,
        ]);

        ShopSubscription::create([
            'shop_id' => $this->store->id,
            'plan_id' => $this->proPlan->id,
            'shopify_charge_id' => 'gid://shopify/AppSubscription/777888',
            'status' => ShopSubscription::STATUS_ACTIVE,
            'current_period_end' => now()->addDays(30),
            'activated_at' => now(),
        ]);
    }

    protected function generateSessionToken(array $overrides = []): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => "https://{$this->store->shop}/admin",
            'dest' => "https://{$this->store->shop}",
            'aud' => $this->testApiKey,
            'sub' => '12345678',
            'exp' => $now + 3600,
            'nbf' => $now - 10,
            'iat' => $now,
            'jti' => 'jwt_nonce_'.uniqid(),
        ], $overrides);

        return JWT::encode($payload, $this->testApiSecret, 'HS256');
    }

    public function test_support_page_renders_complete_documentation_and_guides_with_id_token(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.support.index');

        // Header titles
        $response->assertSee('Help & Support');
        $response->assertSee('Guidance on theme extensions, storefront testing, and troubleshooting');

        // Storefront Theme Activation Guide
        $response->assertSee('Storefront Theme Activation Guide');
        $response->assertSee('Add Your Social Channels');
        $response->assertSee('Open Theme Editor App Embeds');
        $response->assertSee('Enable & Save', false);
        $response->assertSee('Open Shopify Theme Editor (App Embeds)');

        // Wishlist Setup Documentation
        $response->assertSee('How to Set Up Wishlist');
        $response->assertSee('Automated 1-Click Wishlist Setup');
        $response->assertSee('Create a Wishlist Page in Shopify');
        $response->assertSee('Add the SocialNest Wishlist App Block');
        $response->assertSee('/pages/wishlist');
        $response->assertSee('SocialNest Wishlist');
        $response->assertSee('Open Theme Editor with Wishlist Block');

        // Important notes & FAQ
        $response->assertSee('Important Notes');
        $response->assertSee('localStorage');
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('How does WhatsApp sharing work on product pages?');
        $response->assertSee('Will the icons slow down my storefront?');
        $response->assertSee('Can I reposition the icons if they block my chat widget?');
        $response->assertSee('Storefront Settings');

        // Verified store info
        $response->assertSee($this->store->shop);
        $response->assertSee('Shopify Online Store 2.0');
    }

    public function test_support_page_preserves_navigation_links_with_query_parameters(): void
    {
        $token = $this->generateSessionToken();
        $host = 'YWRtaW4uc2hvcGlmeS5jb20vc3RvcmUvc3VwcG9ydC1tZXJjaGFudA';

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get("/shopify/support?shop={$this->store->shop}&host={$host}");

        $response->assertStatus(200);

        // Internal navigation links contain shop and host
        $response->assertSee(e(route('shopify.social-links.index', ['shop' => $this->store->shop, 'host' => $host])), false);
        $response->assertSee(e(route('shopify.settings.index', ['shop' => $this->store->shop, 'host' => $host])), false);
        $response->assertSee(e(route('shopify.wishlist.setup', ['shop' => $this->store->shop, 'host' => $host])), false);
    }

    public function test_unauthenticated_request_to_support_page_redirects_to_install(): void
    {
        $response = $this->get('/shopify/support');

        $response->assertStatus(302);
        $response->assertRedirect(route('shopify.install'));
    }

    public function test_support_page_navigation_missing_bearer_token_returns_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(401);
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
    }

    public function test_support_page_automated_wishlist_setup_action_with_id_token(): void
    {
        $token = $this->generateSessionToken();
        $apiVersion = config('shopify.api_version', '2026-10');
        $graphqlEndpoint = "https://{$this->store->shop}/admin/api/{$apiVersion}/graphql.json";
        $themeId = 'gid://shopify/OnlineStoreTheme/999111';

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // 1. getThemes query
                ->push([
                    'data' => [
                        'themes' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => $themeId,
                                        'name' => 'Dawn',
                                        'role' => 'MAIN',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // 2. getThemeFiles query (templates/page.wishlist.json exists)
                ->push([
                    'data' => [
                        'theme' => [
                            'id' => $themeId,
                            'name' => 'Dawn',
                            'role' => 'MAIN',
                            'files' => [
                                'edges' => [
                                    [
                                        'node' => [
                                            'filename' => 'templates/page.wishlist.json',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // 3. Page search query
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/555111',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => 'wishlist',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/shopify/wishlist/setup', ['target' => 'mainSection']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'created' => false,
        ]);
        $this->assertNotEmpty($response->json('deep_link_url'));
        $this->assertStringContainsString('admin/themes/current/editor', $response->json('deep_link_url'));
    }

    public function test_support_page_renders_contact_support_with_configured_email_and_mailto_link(): void
    {
        Config::set('shopify.support_email', 'help@socialnest.io');

        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(200);
        $response->assertSee('Contact Official Support');
        $response->assertSee('help@socialnest.io');
        $response->assertSee('mailto:help@socialnest.io?subject=SocialNest%20Support%20Request%20-%20'.rawurlencode($this->store->shop), false);
        $response->assertSee('Ticket Response Policy');
        $response->assertSee('Store Diagnostics');
        $response->assertSee($this->store->shop);
    }

    public function test_support_page_renders_default_production_email_when_support_email_is_not_configured(): void
    {
        Config::set('shopify.support_email', null);

        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(200);
        $response->assertSee('Contact Official Support');
        $response->assertSee('support@socialnest.io');
        $response->assertDontSee('Configuration Placeholder');
        $response->assertDontSee('SUPPORT_EMAIL');
        $response->assertDontSee('Email Developer Support');
    }

    public function test_support_page_renders_product_share_and_troubleshooting_sections(): void
    {
        $token = $this->generateSessionToken();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Shopify-Navigation' => '1',
        ])->get('/shopify/support');

        $response->assertStatus(200);

        // Product Share Section
        $response->assertSee('Product Page Sharing & Floating Dock', false);
        $response->assertSee('Enabling & Disabling Product Share', false);
        $response->assertSee('Floating Dock vs. Inline Share', false);
        $response->assertSee('Supported Sharing Platforms');
        $response->assertSee('Pinterest');
        $response->assertSee('Telegram');
        $response->assertSee('Native Web Share');

        // Troubleshooting Section
        $response->assertSee('Troubleshooting & Self-Help Guide', false);
        $response->assertSee('Floating Social Dock Is Not Showing');
        $response->assertSee('Wishlist Page Shows 404 Not Found');
        $response->assertSee('Storefront Changes Do Not Update Instantly');
        $response->assertSee('Dock Collides With Mobile Chat or Cart Buttons');
    }

    public function test_public_support_page_is_accessible_without_authentication(): void
    {
        Config::set('shopify.support_email', 'contact@socialnest.io');

        $response = $this->get('/support');

        $response->assertStatus(200);
        $response->assertViewIs('support.public');
        $response->assertSee('Help & Support', false);
        $response->assertSee('Contact Official Support');
        $response->assertSee('contact@socialnest.io');
        $response->assertSee('mailto:contact@socialnest.io', false);
        $response->assertSee('Getting Started with SocialNest');
        $response->assertSee('How to Set Up Wishlist');
        $response->assertSee('Product Page Sharing & Floating Dock', false);
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('Troubleshooting & Self-Help Guide', false);

        // Crucial security & privacy boundary:
        // Must NOT leak private merchant store information or authenticated setup scripts
        $response->assertDontSee($this->store->shop);
        $response->assertDontSee('btn-auto-setup-wishlist');
        $response->assertDontSee('runAutomatedWishlistSetup');
    }

    public function test_welcome_page_header_and_footer_include_public_support_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('support.public'), false);
        $response->assertSee('Support');
        $response->assertSee('Support &amp; Help Center', false);
    }
}
