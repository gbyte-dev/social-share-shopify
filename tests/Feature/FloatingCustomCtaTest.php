<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\SocialLinkClick;
use App\Services\AnalyticsService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FloatingCustomCtaTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->store = ShopifyStore::create([
            'shop' => 'store-floating-cta.myshopify.com',
            'access_token' => 'shpat_test_cta_token',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->plan = Plan::create([
            'name' => 'SocialNest Pro',
            'slug' => 'socialnest-pro',
            'price' => 5.00,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'is_active' => true,
        ]);

        // Activate dummy subscription
        $subscriptionService = app(SubscriptionService::class);
        $subscriptionService->activateSubscription($this->store, $this->plan);
    }

    protected function authStore(ShopifyStore $store): self
    {
        return $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => base64_encode($store->shop.'/admin'),
        ]);
    }

    public function test_default_floating_cta_settings_are_populated(): void
    {
        $settings = $this->store->getStorefrontSettings();

        $this->assertFalse($settings->floating_cta_enabled);
        $this->assertNull($settings->floating_cta_app_name);
        $this->assertEquals('Get Started', $settings->floating_cta_button_text);
        $this->assertNull($settings->floating_cta_destination_url);
        $this->assertNull($settings->floating_cta_icon_path);
        $this->assertTrue($settings->floating_cta_show_icon);
        $this->assertEquals('rgba(17, 24, 39, 1)', $settings->floating_cta_background_color);
        $this->assertEquals('rgba(255, 255, 255, 1)', $settings->floating_cta_text_color);
        $this->assertEquals('medium', $settings->floating_cta_button_size);
        $this->assertTrue($settings->floating_cta_desktop_enabled);
        $this->assertTrue($settings->floating_cta_mobile_enabled);
    }

    public function test_settings_page_renders_floating_custom_cta_section(): void
    {
        $response = $this->authStore($this->store)->get('/shopify/settings');

        $response->assertStatus(200);
        $response->assertSee('Floating Custom CTA');
        $response->assertSee('floating_cta_enabled');
        $response->assertSee('floating_cta_app_name');
        $response->assertSee('floating_cta_button_text');
        $response->assertSee('floating_cta_destination_url');
        $response->assertSee('floating_cta_button_size');
        $response->assertSee('floating_cta_desktop_enabled');
        $response->assertSee('floating_cta_mobile_enabled');
    }

    public function test_floating_cta_settings_can_be_updated_and_persisted(): void
    {
        $response = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_enabled' => '1',
            'floating_cta_app_name' => 'SocialNest VIP',
            'floating_cta_button_text' => 'Claim 20% Off',
            'floating_cta_destination_url' => 'https://example.com/promo',
            'floating_cta_show_icon' => '1',
            'floating_cta_background_color' => '#4f46e5',
            'floating_cta_text_color' => '#ffffff',
            'floating_cta_button_size' => 'large',
            'floating_cta_desktop_enabled' => '1',
            'floating_cta_mobile_enabled' => '0',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->store->id,
            'floating_cta_enabled' => true,
            'floating_cta_app_name' => 'SocialNest VIP',
            'floating_cta_button_text' => 'Claim 20% Off',
            'floating_cta_destination_url' => 'https://example.com/promo',
            'floating_cta_show_icon' => true,
            'floating_cta_background_color' => '#4f46e5',
            'floating_cta_text_color' => '#ffffff',
            'floating_cta_button_size' => 'large',
            'floating_cta_desktop_enabled' => true,
            'floating_cta_mobile_enabled' => false,
        ]);
    }

    public function test_storefront_config_api_returns_cta_payload(): void
    {
        $settings = $this->store->getStorefrontSettings();
        $settings->update([
            'floating_cta_enabled' => true,
            'floating_cta_app_name' => 'Brand Deal',
            'floating_cta_button_text' => 'Shop Collection',
            'floating_cta_destination_url' => 'https://mystore.com/deal',
            'floating_cta_show_icon' => true,
            'floating_cta_background_color' => '#10b981',
            'floating_cta_text_color' => '#ffffff',
            'floating_cta_button_size' => 'compact',
            'floating_cta_desktop_enabled' => true,
            'floating_cta_mobile_enabled' => true,
        ]);

        $response = $this->getJson("/api/storefront/config?shop={$this->store->shop}");

        $response->assertStatus(200);
        $this->assertTrue($response->json('settings.floating_cta_enabled'));
        $this->assertTrue($response->json('settings.cta_enabled'));
        $this->assertEquals('Brand Deal', $response->json('settings.cta_app_name'));
        $this->assertEquals('Shop Collection', $response->json('settings.cta_text'));
        $this->assertEquals('https://mystore.com/deal', $response->json('settings.cta_url'));
        $this->assertEquals('#10b981', $response->json('settings.cta_background_color'));
        $this->assertEquals('compact', $response->json('settings.cta_size'));
    }

    public function test_inactive_subscription_disables_storefront_cta(): void
    {
        $settings = $this->store->getStorefrontSettings();
        $settings->update([
            'floating_cta_enabled' => true,
            'floating_cta_button_text' => 'Shop Now',
            'floating_cta_destination_url' => 'https://example.com',
        ]);

        // Cancel subscription
        $this->store->subscriptions()->update(['status' => 'cancelled']);

        $response = $this->getJson("/api/storefront/config?shop={$this->store->shop}");

        $response->assertStatus(200);
        $this->assertFalse($response->json('settings.floating_cta_enabled'));
        $this->assertFalse($response->json('settings.cta_enabled'));
    }

    public function test_destination_url_rejects_unsafe_schemes(): void
    {
        $unsafeUrls = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'file:///etc/passwd',
            'vbscript:msgbox',
        ];

        foreach ($unsafeUrls as $url) {
            $response = $this->authStore($this->store)->put('/shopify/settings', [
                'position' => 'bottom_right',
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
                'hover_animation' => 'lift',
                'floating_cta_destination_url' => $url,
            ]);

            $response->assertSessionHasErrors(['floating_cta_destination_url']);
        }
    }

    public function test_icon_upload_and_replacement_works(): void
    {
        $pngFile = UploadedFile::fake()->image('cta-icon.png', 64, 64);

        $response = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_icon' => $pngFile,
        ]);

        $response->assertSessionHasNoErrors();

        $settings = $this->store->fresh()->storefrontSettings;
        $this->assertNotNull($settings->floating_cta_icon_path);
        Storage::disk('public')->assertExists($settings->floating_cta_icon_path);

        $firstPath = $settings->floating_cta_icon_path;

        // Replace icon with new file
        $newWebpFile = UploadedFile::fake()->image('new-icon.webp', 128, 128);
        $replaceResponse = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_icon' => $newWebpFile,
        ]);

        $replaceResponse->assertSessionHasNoErrors();

        $updatedSettings = $this->store->fresh()->storefrontSettings;
        $this->assertNotEquals($firstPath, $updatedSettings->floating_cta_icon_path);
        Storage::disk('public')->assertExists($updatedSettings->floating_cta_icon_path);
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_icon_removal_deletes_file_and_resets_path(): void
    {
        $pngFile = UploadedFile::fake()->image('cta-icon.png', 64, 64);
        $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_icon' => $pngFile,
        ]);

        $settings = $this->store->fresh()->storefrontSettings;
        $savedPath = $settings->floating_cta_icon_path;
        Storage::disk('public')->assertExists($savedPath);

        // Remove icon
        $removeResponse = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'remove_floating_cta_icon' => '1',
        ]);

        $removeResponse->assertSessionHasNoErrors();

        $freshSettings = $this->store->fresh()->storefrontSettings;
        $this->assertNull($freshSettings->floating_cta_icon_path);
        Storage::disk('public')->assertMissing($savedPath);
    }

    public function test_safe_svg_icon_is_accepted_and_malicious_svg_is_rejected(): void
    {
        $safeSvgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>';
        $safeSvg = UploadedFile::fake()->createWithContent('safe.svg', $safeSvgContent);

        $responseSafe = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_icon' => $safeSvg,
        ]);

        $responseSafe->assertSessionHasNoErrors();
        $this->assertNotNull($this->store->fresh()->storefrontSettings->floating_cta_icon_path);

        // Malicious SVG with embedded script
        $evilSvgContent = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script><circle cx="5" cy="5" r="5"/></svg>';
        $evilSvg = UploadedFile::fake()->createWithContent('evil.svg', $evilSvgContent);

        $responseEvil = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_icon' => $evilSvg,
        ]);

        $responseEvil->assertSessionHasErrors(['floating_cta_icon']);
    }

    public function test_floating_cta_click_tracking_records_separately(): void
    {
        $response = $this->postJson("/api/storefront/track-click?shop={$this->store->shop}", [
            'platform' => 'floating_cta',
            'link_type' => 'floating_cta',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->store->id,
            'platform' => 'floating_cta',
            'link_type' => 'floating_cta',
        ]);
    }

    public function test_floating_cta_clicks_are_excluded_from_social_platform_analytics(): void
    {
        // 1. Create a social click
        SocialLinkClick::create([
            'shopify_store_id' => $this->store->id,
            'platform' => 'instagram',
            'link_type' => 'dock',
            'clicked_at' => now(),
        ]);

        // 2. Create a floating CTA click
        SocialLinkClick::create([
            'shopify_store_id' => $this->store->id,
            'platform' => 'floating_cta',
            'link_type' => 'floating_cta',
            'clicked_at' => now(),
        ]);

        $analytics = app(AnalyticsService::class);
        $dateRange = $analytics->resolveDateRange('last_30_days');
        $platformChart = $analytics->getPlatformClicksChart($this->store, $dateRange);

        $platforms = $platformChart['platform_keys'] ?? [];
        $this->assertContains('instagram', $platforms);
        $this->assertNotContains('floating_cta', $platforms);
        $this->assertNotContains('cta', $platforms);
    }

    public function test_multi_shop_configuration_isolation(): void
    {
        $otherStore = ShopifyStore::create([
            'shop' => 'store-other-cta.myshopify.com',
            'access_token' => 'shpat_other_token',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        app(SubscriptionService::class)->activateSubscription($otherStore, $this->plan);

        // Store 1 sets custom text
        $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_button_text' => 'Store 1 Exclusive',
        ]);

        // Store 2 sets different text
        $this->authStore($otherStore)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'floating_cta_button_text' => 'Store 2 Summer Sale',
        ]);

        $this->assertEquals('Store 1 Exclusive', $this->store->fresh()->storefrontSettings->floating_cta_button_text);
        $this->assertEquals('Store 2 Summer Sale', $otherStore->fresh()->storefrontSettings->floating_cta_button_text);
    }
}
