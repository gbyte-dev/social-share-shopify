<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Services\LogoDiscoveryService;
use App\Services\LogoProcessingService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomSocialPlatformFlowTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected string $testApiSecret = 'test_shopify_api_secret_456_minimum_32_bytes_length';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'shopify.api_key' => 'test_shopify_api_key_123',
            'shopify.api_secret' => $this->testApiSecret,
        ]);

        $this->store = ShopifyStore::create([
            'shop' => '26-sep.myshopify.com',
            'access_token' => 'shpat_test_token_12345',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function generateSessionToken(ShopifyStore $store): string
    {
        $now = time();
        $payload = [
            'iss' => "https://{$store->shop}/admin",
            'dest' => "https://{$store->shop}",
            'aud' => 'test_shopify_api_key_123',
            'sub' => '12345678',
            'exp' => $now + 3600,
            'nbf' => $now - 10,
            'iat' => $now,
            'jti' => 'jwt_nonce_' . uniqid(),
        ];

        return JWT::encode($payload, $this->testApiSecret, 'HS256');
    }

    protected function authStore(): self
    {
        $token = $this->generateSessionToken($this->store);

        return $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
            'shopify_host' => base64_encode("admin.shopify.com/store/{$this->store->shop}"),
        ])->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Test 1: Existing registered custom platform (like kick) can be selected and added to store
     * with MediaFire destination URL and custom label without loopback download or SSRF errors.
     */
    public function test_can_add_custom_platform_with_mediafire_url_and_custom_label(): void
    {
        // Simulate existing global custom platform
        $logoData = app(LogoProcessingService::class)->createDefaultSvgLogo($this->store->id, 'kick');
        $logoUrl = Storage::disk('public')->url($logoData['logo_small_path']);

        DB::table('global_custom_platforms')->insert([
            'name' => 'kick',
            'website_url' => 'https://kick.com',
            'logo_url' => $logoUrl,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->authStore()->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'kick',
            'official_website_url' => 'https://kick.com',
            'selected_logo_url' => $logoUrl,
            'url' => 'https://www.mediafire.com/file/example123/kick_app.zip',
            'label' => 'Follow us',
            'sort_order' => 2,
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->store->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'kick',
            'url' => 'https://www.mediafire.com/file/example123/kick_app.zip',
            'label' => 'Follow us',
            'is_active' => true,
        ]);

        $link = SocialLink::where('shopify_store_id', $this->store->id)
            ->where('custom_platform_name', 'kick')
            ->first();

        $this->assertNotNull($link);
        $this->assertNotNull($link->logo_small_url);
        $this->assertStringNotContainsString('/public/uploads/', $link->logo_small_url);
    }

    /**
     * Test 2: Logo URL generation correctly normalizes legacy paths containing /public/uploads/.
     */
    public function test_resolve_logo_url_strips_erroneous_public_uploads(): void
    {
        $link = new SocialLink;

        // Case A: Full URL with /public/uploads/
        $legacyUrl = 'https://socialvire.webvire.com/public/uploads/logos/10/test.png';
        $resolved = $link->resolveLogoUrl($legacyUrl);
        $this->assertEquals('https://socialvire.webvire.com/uploads/logos/10/test.png', $resolved);

        // Case B: Relative path with public/
        $relativePath = 'public/uploads/logos/10/test.png';
        $resolvedB = $link->resolveLogoUrl($relativePath);
        $this->assertEquals(asset('uploads/logos/10/test.png'), $resolvedB);

        // Case C: Standard logos/ path on public storage disk
        $storagePath = 'logos/10/icon_36x36.png';
        $resolvedC = $link->resolveLogoUrl($storagePath);
        $this->assertEquals(Storage::disk('public')->url('logos/10/icon_36x36.png'), $resolvedC);

        // Case D: Data URI
        $dataUri = 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=';
        $resolvedD = $link->resolveLogoUrl($dataUri);
        $this->assertEquals($dataUri, $resolvedD);
    }

    /**
     * Test 3: Logo discovery handles website crawl timeout gracefully and falls back to other sources.
     */
    public function test_logo_discovery_handles_website_timeout_gracefully(): void
    {
        // Mock kick.com timeout, while Unavatar responds successfully
        Http::fake([
            'https://kick.com' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
            'https://unavatar.io/kick.com' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
                200,
                ['Content-Type' => 'image/png']
            ),
            '*' => Http::response('', 404),
        ]);

        $service = app(LogoDiscoveryService::class);
        $result = $service->discover('kick', null, 'https://kick.com');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['candidates']);
        $this->assertEquals('https://unavatar.io/kick.com', $result['candidates'][0]['url']);
    }

    /**
     * Test 4: Logo discovery returns a default platform candidate when all external sources fail.
     */
    public function test_logo_discovery_returns_default_candidate_when_all_fail(): void
    {
        Http::fake([
            '*' => Http::response('', 404),
        ]);

        $service = app(LogoDiscoveryService::class);
        $result = $service->discover('UnknownPlatformXYZ123', null, 'https://nonexistent-domain-xyz.com');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['candidates']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $result['candidates'][0]['url']);
    }

    /**
     * Test 5: Custom platform creation without logo automatically generates a clean default SVG logo.
     */
    public function test_custom_platform_creation_without_logo_uses_default_svg(): void
    {
        $response = $this->authStore()->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'AutoDefaultApp',
            'url' => 'https://example.com/profile',
            'label' => 'Auto App',
        ]);

        $response->assertSessionHasNoErrors();

        $link = SocialLink::where('shopify_store_id', $this->store->id)
            ->where('custom_platform_name', 'AutoDefaultApp')
            ->first();

        $this->assertNotNull($link);
        $this->assertNotNull($link->logo_small_path);
        Storage::disk('public')->assertExists($link->logo_small_path);
    }

    /**
     * Test 6: Global custom platform registration reuses existing entry if name already registered.
     */
    public function test_store_global_custom_app_reuses_existing_platform(): void
    {
        DB::table('global_custom_platforms')->insert([
            'name' => 'Rumble',
            'website_url' => 'https://rumble.com',
            'logo_url' => 'https://socialvire.webvire.com/uploads/logos/10/rumble.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->authStore()->postJson('/shopify/social-links/store-global-custom-app', [
            'name' => 'rumble',
            'website_url' => 'https://rumble.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'app' => [
                'name' => 'Rumble',
                'website' => 'https://rumble.com',
                'logo' => 'https://socialvire.webvire.com/uploads/logos/10/rumble.png',
            ],
        ]);
    }

    /**
     * Test 7: Storefront API returns custom platforms with correct metadata and logo URL.
     */
    public function test_storefront_api_returns_custom_platform_with_logo(): void
    {
        $this->store->getStorefrontSettings()->update(['is_enabled' => true]);

        $logoData = app(LogoProcessingService::class)->createDefaultSvgLogo($this->store->id, 'kick');

        $this->store->socialLinks()->create([
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'kick',
            'url' => 'https://www.mediafire.com/file/123',
            'label' => 'Follow us',
            'logo_small_path' => $logoData['logo_small_path'],
            'logo_medium_path' => $logoData['logo_medium_path'],
            'logo_large_path' => $logoData['logo_large_path'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson("/api/storefront/config?shop={$this->store->shop}");

        $response->assertOk();
        $response->assertJsonFragment([
            'platform' => 'other',
            'platform_type' => 'custom',
            'is_custom' => true,
            'name' => 'kick',
            'custom_name' => 'kick',
            'url' => 'https://www.mediafire.com/file/123',
            'label' => 'Follow us',
        ]);

        $links = $response->json('links');
        $this->assertCount(1, $links);
        $this->assertNotNull($links[0]['logo_small']);
        $this->assertStringNotContainsString('/public/uploads/', $links[0]['logo_small']);
    }

    /**
     * Test 8: Duplicate custom platform name on same store is rejected by unique validation.
     */
    public function test_duplicate_custom_platform_on_same_store_rejected(): void
    {
        $this->store->socialLinks()->create([
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'kick',
            'url' => 'https://kick.com/original',
            'label' => 'Original Kick',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->authStore()->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'kick',
            'url' => 'https://www.mediafire.com/file/another',
            'label' => 'Follow us again',
        ]);

        $response->assertSessionHasErrors('custom_platform_name');
    }
}
