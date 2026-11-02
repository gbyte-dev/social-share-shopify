<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Services\LogoDiscoveryService;
use App\Services\LogoProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomSocialPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->storeA = ShopifyStore::create([
            'shop' => 'store-alpha.myshopify.com',
            'access_token' => 'token_alpha',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->storeB = ShopifyStore::create([
            'shop' => 'store-beta.myshopify.com',
            'access_token' => 'token_beta',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function authStore(ShopifyStore $store): self
    {
        return $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => base64_encode("admin.shopify.com/store/{$store->shop}"),
        ]);
    }

    /**
     * Create a realistic transparent test PNG binary.
     */
    protected function createTestPngData(int $w = 120, int $h = 120): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $w, $h, $transparent);

        // Draw simple colored circle in the center
        $blue = imagecolorallocate($im, 88, 101, 242);
        imagefilledellipse($im, (int) ($w / 2), (int) ($h / 2), (int) ($w * 0.8), (int) ($h * 0.8), $blue);

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return $data;
    }

    public function test_can_create_custom_social_platform_with_manual_logo_upload(): void
    {
        $pngData = $this->createTestPngData(150, 150);
        $file = UploadedFile::fake()->createWithContent('discord_logo.png', $pngData);

        $response = $this->authStore($this->storeA)->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'Discord',
            'official_website_url' => 'https://discord.com',
            'url' => 'https://discord.gg/mycommunity',
            'label' => 'Join Discord',
            'alt_text' => 'Discord Community Server',
            'sort_order' => 1,
            'is_active' => '1',
            'logo_file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('shopify.social-links.index'));

        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'Discord',
            'url' => 'https://discord.gg/mycommunity',
            'label' => 'Join Discord',
            'alt_text' => 'Discord Community Server',
            'is_active' => true,
        ]);

        $link = SocialLink::where('custom_platform_name', 'Discord')->first();
        $this->assertNotNull($link);
        $this->assertTrue($link->isCustom());
        $this->assertEquals('Discord', $link->platform_name);

        // Verify storage files exist
        Storage::disk('public')->assertExists($link->original_logo_path);
        Storage::disk('public')->assertExists($link->logo_small_path);
        Storage::disk('public')->assertExists($link->logo_medium_path);
        Storage::disk('public')->assertExists($link->logo_large_path);
    }

    public function test_image_variants_generated_at_exact_dimensions(): void
    {
        $pngData = $this->createTestPngData(200, 100); // 2:1 aspect ratio test
        $service = app(LogoProcessingService::class);

        $results = $service->processBinaryData($pngData, $this->storeA->id, 'wide_banner.png');

        $disk = Storage::disk('public');
        $this->assertTrue($disk->exists($results['logo_small_path']));
        $this->assertTrue($disk->exists($results['logo_medium_path']));
        $this->assertTrue($disk->exists($results['logo_large_path']));

        // Inspect Small variant (36x36)
        $smallInfo = getimagesizefromstring($disk->get($results['logo_small_path']));
        $this->assertEquals(36, $smallInfo[0]);
        $this->assertEquals(36, $smallInfo[1]);

        // Inspect Medium variant (44x44)
        $mediumInfo = getimagesizefromstring($disk->get($results['logo_medium_path']));
        $this->assertEquals(44, $mediumInfo[0]);
        $this->assertEquals(44, $mediumInfo[1]);

        // Inspect Large variant (56x56)
        $largeInfo = getimagesizefromstring($disk->get($results['logo_large_path']));
        $this->assertEquals(56, $largeInfo[0]);
        $this->assertEquals(56, $largeInfo[1]);
    }

    public function test_supported_image_formats_png_jpeg_webp_gif(): void
    {
        $service = app(LogoProcessingService::class);

        // 1. JPEG
        $imJpg = imagecreatetruecolor(50, 50);
        ob_start();
        imagejpeg($imJpg);
        $jpgData = ob_get_clean();
        imagedestroy($imJpg);
        $resJpg = $service->processBinaryData($jpgData, $this->storeA->id, 'test.jpg');
        $this->assertNotNull($resJpg['logo_medium_path']);

        // 2. WebP
        $imWebp = imagecreatetruecolor(50, 50);
        ob_start();
        imagewebp($imWebp);
        $webpData = ob_get_clean();
        imagedestroy($imWebp);
        $resWebp = $service->processBinaryData($webpData, $this->storeA->id, 'test.webp');
        $this->assertNotNull($resWebp['logo_medium_path']);

        // 3. GIF
        $imGif = imagecreatetruecolor(50, 50);
        ob_start();
        imagegif($imGif);
        $gifData = ob_get_clean();
        imagedestroy($imGif);
        $resGif = $service->processBinaryData($gifData, $this->storeA->id, 'test.gif');
        $this->assertNotNull($resGif['logo_medium_path']);
    }

    public function test_invalid_and_corrupt_upload_rejected(): void
    {
        $corruptFile = UploadedFile::fake()->createWithContent('fake.png', 'This is plain text pretending to be a PNG');

        $response = $this->authStore($this->storeA)->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'Bad Platform',
            'url' => 'https://example.com',
            'logo_file' => $corruptFile,
        ]);

        $response->assertSessionHasErrors('logo_file');
        $this->assertDatabaseMissing('social_links', ['custom_platform_name' => 'Bad Platform']);
    }

    public function test_malicious_svg_rejected(): void
    {
        $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("XSS")</script><circle cx="10" cy="10" r="5"/></svg>';
        $svgFile = UploadedFile::fake()->createWithContent('exploit.svg', $maliciousSvg);

        $response = $this->authStore($this->storeA)->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'Hacker Platform',
            'url' => 'https://example.com',
            'logo_file' => $svgFile,
        ]);

        $response->assertSessionHasErrors('logo_file');
        $this->assertDatabaseMissing('social_links', ['custom_platform_name' => 'Hacker Platform']);
    }

    public function test_safe_svg_accepted_and_sanitized(): void
    {
        $safeSvg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#4f46e5"/></svg>';
        $svgFile = UploadedFile::fake()->createWithContent('safe.svg', $safeSvg);

        $response = $this->authStore($this->storeA)->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'Safe SVG App',
            'url' => 'https://example.com',
            'logo_file' => $svgFile,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('social_links', ['custom_platform_name' => 'Safe SVG App']);
    }

    public function test_logo_discovery_finds_candidates_with_mocked_http(): void
    {
        $testPng = $this->createTestPngData(80, 80);

        Http::fake([
            'https://telegram.org' => Http::response('<html><head><link rel="apple-touch-icon" href="/static/t_logo.png"></head><body>Telegram</body></html>', 200),
            'https://telegram.org/static/t_logo.png' => Http::response($testPng, 200, ['Content-Type' => 'image/png']),
            'https://logo.clearbit.com/telegram.org' => Http::response($testPng, 200, ['Content-Type' => 'image/png']),
            'https://www.google.com/s2/favicons*' => Http::response($testPng, 200, ['Content-Type' => 'image/png']),
            'https://icons.duckduckgo.com/*' => Http::response($testPng, 200, ['Content-Type' => 'image/x-icon']),
        ]);

        $response = $this->authStore($this->storeA)->postJson('/shopify/social-links/discover-logos', [
            'name' => 'Telegram',
            'url' => 'https://t.me/mychannel',
            'website_url' => 'https://telegram.org',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $candidates = $response->json('candidates');
        $this->assertNotEmpty($candidates);
        $this->assertNotNull($response->json('preselected'));
    }

    public function test_create_custom_platform_with_discovered_logo_url(): void
    {
        $testPng = $this->createTestPngData(100, 100);

        Http::fake([
            'https://assets.example.com/discovered-logo.png' => Http::response($testPng, 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->authStore($this->storeA)->post('/shopify/social-links', [
            'platform' => 'other',
            'custom_platform_name' => 'Reddit',
            'url' => 'https://reddit.com/r/myshop',
            'selected_logo_url' => 'https://assets.example.com/discovered-logo.png',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'custom_platform_name' => 'Reddit',
            'logo_source_url' => 'https://assets.example.com/discovered-logo.png',
            'logo_source_domain' => 'assets.example.com',
        ]);

        $link = SocialLink::where('custom_platform_name', 'Reddit')->first();
        Storage::disk('public')->assertExists($link->logo_small_path);
        Storage::disk('public')->assertExists($link->logo_medium_path);
        Storage::disk('public')->assertExists($link->logo_large_path);
    }

    public function test_ssrf_protection_blocks_dangerous_addresses(): void
    {
        $service = app(LogoDiscoveryService::class);

        $this->assertFalse($service->isSafeUrl('http://localhost/logo.png'));
        $this->assertFalse($service->isSafeUrl('http://127.0.0.1/logo.png'));
        $this->assertFalse($service->isSafeUrl('http://127.0.0.254/secret'));
        $this->assertFalse($service->isSafeUrl('http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($service->isSafeUrl('http://10.0.0.5/admin'));
        $this->assertFalse($service->isSafeUrl('http://192.168.1.100/logo.png'));
        $this->assertFalse($service->isSafeUrl('http://172.16.0.1/logo.png'));
        $this->assertFalse($service->isSafeUrl('javascript:alert(1)'));
        $this->assertFalse($service->isSafeUrl('file:///etc/passwd'));
        $this->assertFalse($service->isSafeUrl('http://internal.service.local/logo.png'));
    }

    public function test_edit_custom_platform_and_replace_logo(): void
    {
        $pngA = $this->createTestPngData(60, 60);
        $service = app(LogoProcessingService::class);
        $logoA = $service->processBinaryData($pngA, $this->storeA->id, 'orig.png');

        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'Initial App',
            'url' => 'https://initial.com',
            'original_logo_path' => $logoA['original_logo_path'],
            'logo_small_path' => $logoA['logo_small_path'],
            'logo_medium_path' => $logoA['logo_medium_path'],
            'logo_large_path' => $logoA['logo_large_path'],
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists($logoA['logo_medium_path']);

        // Upload replacement logo
        $pngB = $this->createTestPngData(80, 80);
        $replacementFile = UploadedFile::fake()->createWithContent('replacement.png', $pngB);

        $response = $this->authStore($this->storeA)->put("/shopify/social-links/{$link->id}", [
            'custom_platform_name' => 'Updated App',
            'url' => 'https://updated.com',
            'logo_file' => $replacementFile,
        ]);

        $response->assertSessionHasNoErrors();

        $link->refresh();
        $this->assertEquals('Updated App', $link->custom_platform_name);
        $this->assertEquals('https://updated.com', $link->url);

        // Old logo files must be cleaned up
        Storage::disk('public')->assertMissing($logoA['logo_medium_path']);

        // New logo files must exist
        Storage::disk('public')->assertExists($link->logo_medium_path);
    }

    public function test_delete_custom_platform_cleans_up_files(): void
    {
        $png = $this->createTestPngData(60, 60);
        $service = app(LogoProcessingService::class);
        $logoData = $service->processBinaryData($png, $this->storeA->id, 'delete_me.png');

        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'Delete Me App',
            'url' => 'https://deleteme.com',
            'original_logo_path' => $logoData['original_logo_path'],
            'logo_small_path' => $logoData['logo_small_path'],
            'logo_medium_path' => $logoData['logo_medium_path'],
            'logo_large_path' => $logoData['logo_large_path'],
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists($logoData['logo_medium_path']);

        $response = $this->authStore($this->storeA)->delete("/shopify/social-links/{$link->id}");
        $response->assertRedirect(route('shopify.social-links.index'));

        $this->assertDatabaseMissing('social_links', ['id' => $link->id]);
        Storage::disk('public')->assertMissing($logoData['logo_medium_path']);
    }

    public function test_store_isolation_for_custom_platforms(): void
    {
        $linkA = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'Store A App',
            'url' => 'https://storea.com',
            'is_active' => true,
        ]);

        // Store B tries to update Store A's link
        $response = $this->authStore($this->storeB)->put("/shopify/social-links/{$linkA->id}", [
            'custom_platform_name' => 'Hijacked App',
            'url' => 'https://hijacked.com',
        ]);

        $response->assertStatus(404);
        $linkA->refresh();
        $this->assertEquals('Store A App', $linkA->custom_platform_name);

        // Store B tries to delete Store A's link
        $delResponse = $this->authStore($this->storeB)->delete("/shopify/social-links/{$linkA->id}");
        $delResponse->assertStatus(404);
        $this->assertDatabaseHas('social_links', ['id' => $linkA->id]);
    }

    public function test_storefront_config_api_returns_custom_logo_data(): void
    {
        $png = $this->createTestPngData(60, 60);
        $service = app(LogoProcessingService::class);
        $logoData = $service->processBinaryData($png, $this->storeA->id, 'storefront_test.png');

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'Snapchat Community',
            'url' => 'https://snapchat.com/add/storealpha',
            'label' => 'Add on Snapchat',
            'alt_text' => 'Snapchat Community Link',
            'original_logo_path' => $logoData['original_logo_path'],
            'logo_small_path' => $logoData['logo_small_path'],
            'logo_medium_path' => $logoData['logo_medium_path'],
            'logo_large_path' => $logoData['logo_large_path'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');

        $response->assertStatus(200);
        $links = $response->json('links');
        $this->assertCount(1, $links);

        $custom = $links[0];
        $this->assertTrue($custom['is_custom']);
        $this->assertEquals('other', $custom['platform']);
        $this->assertEquals('Snapchat Community', $custom['name']);
        $this->assertEquals('Snapchat Community', $custom['custom_name']);
        $this->assertEquals('https://snapchat.com/add/storealpha', $custom['url']);
        $this->assertStringContainsString('_36x36.png', $custom['logo_small']);
        $this->assertStringContainsString('_44x44.png', $custom['logo_medium']);
        $this->assertStringContainsString('_56x56.png', $custom['logo_large']);
    }

    public function test_click_tracking_records_custom_platform_click(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => 'Twitch Stream',
            'url' => 'https://twitch.tv/storealpha',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson('/api/storefront/track-click', [
            'shop' => 'store-alpha.myshopify.com',
            'link_id' => $link->id,
            'platform' => 'other',
            'referrer' => 'https://store-alpha.myshopify.com/products/controller',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->storeA->id,
            'social_link_id' => $link->id,
            'platform' => 'other',
        ]);
    }

    public function test_existing_built_in_platforms_remain_fully_functional(): void
    {
        // 1. Create built-in platform
        $response = $this->authStore($this->storeA)->post('/shopify/social-links', [
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'label' => 'Follow on IG',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $response->assertSessionHasNoErrors();

        $link = SocialLink::where('platform', 'instagram')->first();
        $this->assertNotNull($link);
        $this->assertFalse($link->isCustom());
        $this->assertTrue($link->isBuiltIn());
        $this->assertEquals('Instagram', $link->platform_name);

        // 2. Query storefront API
        $api = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $api->assertStatus(200);
        $apiLinks = $api->json('links');
        $this->assertCount(1, $apiLinks);
        $this->assertEquals('instagram', $apiLinks[0]['platform']);
        $this->assertFalse($apiLinks[0]['is_custom']);

        // 3. Click track built-in platform
        $click = $this->postJson('/api/storefront/track-click', [
            'shop' => 'store-alpha.myshopify.com',
            'link_id' => $link->id,
            'platform' => 'instagram',
        ]);
        $click->assertStatus(200);
    }
}
