<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Models\StorefrontSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSettingsAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = ShopifyStore::create([
            'shop' => 'store-alpha.myshopify.com',
            'access_token' => 'token_alpha_secret_xyz',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->storeB = ShopifyStore::create([
            'shop' => 'store-beta.myshopify.com',
            'access_token' => 'token_beta_secret_xyz',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function authStoreA(): self
    {
        return $this->withSession([
            'shopify_shop' => $this->storeA->shop,
            'shopify_store_id' => $this->storeA->id,
        ]);
    }

    protected function authStoreB(): self
    {
        return $this->withSession([
            'shopify_shop' => $this->storeB->shop,
            'shopify_store_id' => $this->storeB->id,
        ]);
    }

    public function test_unauthenticated_request_to_settings_is_redirected(): void
    {
        $response = $this->get('/shopify/settings');

        $response->assertRedirect(route('shopify.install'));
    }

    public function test_authenticated_merchant_can_view_settings_page(): void
    {
        $response = $this->authStoreA()->get('/shopify/settings');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.settings.index');
        $response->assertSee('Widget Configuration');
        $response->assertSee('Bottom Right');
    }

    public function test_merchant_can_update_settings(): void
    {
        $response = $this->authStoreA()->put('/shopify/settings', [
            'is_enabled' => '1',
            'position' => 'bottom_left',
            'icon_size' => 'large',
            'icon_shape' => 'rounded',
            'show_tooltips' => '0',
            'whatsapp_message' => 'Hi, I need help with this order!',
        ]);

        $response->assertRedirect(route('shopify.settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'bottom_left',
            'icon_size' => 'large',
            'icon_shape' => 'rounded',
            'show_tooltips' => false,
            'whatsapp_message' => 'Hi, I need help with this order!',
        ]);
    }

    public function test_settings_validation_rejects_invalid_values(): void
    {
        $response = $this->authStoreA()->put('/shopify/settings', [
            'position' => 'top_middle_invalid',
            'icon_size' => 'huge_invalid',
            'icon_shape' => 'star_invalid',
        ]);

        $response->assertSessionHasErrors(['position', 'icon_size', 'icon_shape']);
    }

    public function test_merchant_can_save_settings_via_ajax_and_receive_json_response(): void
    {
        $response = $this->authStoreA()->putJson('/shopify/settings', [
            'is_enabled' => '1',
            'position' => 'vertical_left',
            'icon_size' => 'small',
            'icon_shape' => 'square',
            'show_tooltips' => '1',
            'whatsapp_message' => 'AJAX saved message',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Storefront settings saved successfully!',
        ]);
        $response->assertJsonPath('settings.position', 'vertical_left');
        $response->assertJsonPath('settings.icon_size', 'small');

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'position' => 'vertical_left',
            'whatsapp_message' => 'AJAX saved message',
        ]);
    }

    public function test_ajax_settings_validation_returns_json_errors(): void
    {
        $response = $this->authStoreA()->putJson('/shopify/settings', [
            'position' => 'top_middle_invalid',
            'icon_size' => 'huge_invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['position', 'icon_size']);
    }

    public function test_settings_isolation_between_different_stores(): void
    {
        StorefrontSetting::create([
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'vertical_right',
            'icon_size' => 'small',
            'icon_shape' => 'square',
        ]);

        // Store B updates its settings to bottom_left
        $this->authStoreB()->put('/shopify/settings', [
            'is_enabled' => '0',
            'position' => 'bottom_left',
            'icon_size' => 'large',
            'icon_shape' => 'circle',
        ]);

        // Store A settings must remain untouched
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'position' => 'vertical_right',
            'icon_size' => 'small',
            'icon_shape' => 'square',
        ]);

        // Store B has its own settings
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeB->id,
            'position' => 'bottom_left',
            'is_enabled' => false,
        ]);
    }

    public function test_public_storefront_config_api_returns_clean_configuration(): void
    {
        StorefrontSetting::create([
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'show_tooltips' => true,
            'whatsapp_message' => 'Hello from Storefront',
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'label' => 'Follow Store A',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'tiktok',
            'url' => 'https://www.tiktok.com/@storea',
            'label' => 'TikTok Store A',
            'is_active' => false, // Inactive link
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shop' => 'store-alpha.myshopify.com',
            'enabled' => true,
            'settings' => [
                'position' => 'bottom_right',
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
                'show_tooltips' => true,
                'whatsapp_message' => 'Hello from Storefront',
            ],
        ]);

        // Assert only active links are present
        $links = $response->json('links');
        $this->assertCount(1, $links);
        $this->assertEquals('instagram', $links[0]['platform']);
        $this->assertEquals('https://www.instagram.com/storea', $links[0]['url']);

        // Assert sensitive tokens and credentials are NEVER leaked
        $content = $response->getContent();
        $this->assertStringNotContainsString('token_alpha_secret_xyz', $content);
        $this->assertStringNotContainsString('access_token', $content);
        $this->assertStringNotContainsString('api_secret', $content);
    }

    public function test_public_storefront_config_api_rejects_missing_or_unknown_shop(): void
    {
        $responseMissing = $this->getJson('/api/storefront/config');
        $responseMissing->assertStatus(400);

        $responseUnknown = $this->getJson('/api/storefront/config?shop=nonexistent.myshopify.com');
        $responseUnknown->assertStatus(404);
    }

    public function test_public_storefront_click_tracking_records_valid_event(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson('/api/storefront/track-click', [
            'shop' => 'store-alpha.myshopify.com',
            'link_id' => $link->id,
            'platform' => 'instagram',
            'referrer' => 'https://store-alpha.myshopify.com/products/summer-dress',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->storeA->id,
            'social_link_id' => $link->id,
            'platform' => 'instagram',
            'referrer' => 'https://store-alpha.myshopify.com/products/summer-dress',
        ]);

        // Check that click appears in store clicks
        $this->assertEquals(1, $this->storeA->clicks()->count());
    }

    public function test_click_tracking_rejects_cross_store_link_id(): void
    {
        $linkA = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Attempt to log click under Store B using Store A's link ID
        $response = $this->postJson('/api/storefront/track-click', [
            'shop' => 'store-beta.myshopify.com',
            'link_id' => $linkA->id,
            'platform' => 'instagram',
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, $this->storeB->clicks()->count());
    }

    public function test_plans_page_renders_for_authenticated_merchant(): void
    {
        $response = $this->authStoreA()->get('/shopify/plans');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.plans.index');
        $response->assertSee('Free Plan');
        $response->assertSee('Pro Plan');
        $response->assertSee('Shopify Billing API Status');
    }

    public function test_support_page_renders_for_authenticated_merchant(): void
    {
        $response = $this->authStoreA()->get('/shopify/support');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.support.index');
        $response->assertSee('Storefront Theme Activation Guide');
        $response->assertSee('Open Shopify Theme Editor');
        $response->assertSee('How to Set Up Wishlist');
        $response->assertSee('Create a Wishlist Page in Shopify');
        $response->assertSee('Add the SocialNest Wishlist App Block');
        $response->assertSee('/pages/wishlist');
        $response->assertSee('SocialNest Wishlist');
        $response->assertSee('Important Notes');
        $response->assertSee('localStorage');
    }

    public function test_unauthenticated_plans_and_support_requests_are_blocked(): void
    {
        $this->get('/shopify/plans')->assertRedirect(route('shopify.install'));
        $this->get('/shopify/support')->assertRedirect(route('shopify.install'));
    }

    public function test_public_storefront_config_handles_cors_options_preflight(): void
    {
        $response = $this->call('OPTIONS', '/api/storefront/config');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->assertHeader('Access-Control-Allow-Private-Network', 'true');
    }

    public function test_public_storefront_track_click_handles_cors_options_preflight(): void
    {
        $response = $this->call('OPTIONS', '/api/storefront/track-click');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->assertHeader('Access-Control-Allow-Private-Network', 'true');
    }

    public function test_public_storefront_config_supports_theme_editor_preview_fallback(): void
    {
        $response = $this->getJson('/api/storefront/config?shop=uninstalled-store.myshopify.com&preview=1');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_preview' => true,
            'enabled' => true,
        ]);
        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertIsArray($response->json('links'));
    }

    public function test_public_storefront_config_returns_real_store_links_even_in_preview_mode_when_store_is_installed(): void
    {
        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/realstore',
            'label' => 'Real Store Link',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'facebook',
            'url' => 'https://www.facebook.com/realstore',
            'label' => 'Real FB Link',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com&preview=1');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shop' => 'store-alpha.myshopify.com',
            'enabled' => true,
        ]);

        $links = $response->json('links');
        $this->assertCount(2, $links);
        $this->assertEquals('https://www.instagram.com/realstore', $links[0]['url']);
        $this->assertEquals('https://www.facebook.com/realstore', $links[1]['url']);
    }

    public function test_public_storefront_config_sends_no_cache_headers_to_prevent_stale_settings(): void
    {
        $response = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');

        $response->assertStatus(200);
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function test_unified_configuration_identical_between_storefront_and_theme_editor_preview(): void
    {
        StorefrontSetting::create([
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'vertical_left',
            'icon_size' => 'large',
            'icon_shape' => 'square',
            'show_tooltips' => false,
            'whatsapp_message' => 'Shared from Store Alpha',
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/storealpha',
            'label' => 'Alpha IG',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'pinterest',
            'url' => 'https://pinterest.com/storealpha',
            'label' => 'Alpha Pins',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Disabled channel
        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'x',
            'url' => 'https://x.com/storealpha',
            'label' => 'Alpha X',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $storefrontResponse = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $previewResponse = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com&preview=1');

        $storefrontResponse->assertStatus(200);
        $previewResponse->assertStatus(200);

        // Assert both receive identical canonical configuration
        $this->assertSame($storefrontResponse->json('settings'), $previewResponse->json('settings'));
        $this->assertSame($storefrontResponse->json('links'), $previewResponse->json('links'));

        // Assert canonical values match Laravel DB exactly
        $this->assertEquals('vertical_left', $storefrontResponse->json('settings.position'));
        $this->assertEquals('large', $storefrontResponse->json('settings.icon_size'));
        $this->assertEquals('square', $storefrontResponse->json('settings.icon_shape'));
        $this->assertFalse($storefrontResponse->json('settings.show_tooltips'));
        $this->assertTrue($storefrontResponse->json('enabled'));

        // Assert active links match and disabled link is excluded from both
        $this->assertCount(2, $storefrontResponse->json('links'));
        $this->assertCount(2, $previewResponse->json('links'));
        $this->assertEquals('https://instagram.com/storealpha', $storefrontResponse->json('links.0.url'));
        $this->assertEquals('https://pinterest.com/storealpha', $storefrontResponse->json('links.1.url'));
    }

    public function test_dashboard_settings_save_immediately_synchronizes_storefront_and_preview(): void
    {
        // 1. Initial configuration
        $this->authStoreA()->put('/shopify/settings', [
            'is_enabled' => '1',
            'position' => 'bottom_right',
            'icon_size' => 'small',
            'icon_shape' => 'circle',
            'show_tooltips' => '1',
        ]);

        $res1 = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $this->assertEquals('bottom_right', $res1->json('settings.position'));
        $this->assertEquals('small', $res1->json('settings.icon_size'));

        // 2. Merchant saves new settings in Dashboard
        $this->authStoreA()->put('/shopify/settings', [
            'is_enabled' => '1',
            'position' => 'vertical_right',
            'icon_size' => 'large',
            'icon_shape' => 'rounded',
            'show_tooltips' => '0',
        ]);

        // 3. Immediately query both storefront and Theme Editor preview
        $storefront = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $preview = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com&preview=1');

        $this->assertEquals('vertical_right', $storefront->json('settings.position'));
        $this->assertEquals('large', $storefront->json('settings.icon_size'));
        $this->assertEquals('rounded', $storefront->json('settings.icon_shape'));
        $this->assertFalse($storefront->json('settings.show_tooltips'));

        $this->assertEquals('vertical_right', $preview->json('settings.position'));
        $this->assertEquals('large', $preview->json('settings.icon_size'));
        $this->assertEquals('rounded', $preview->json('settings.icon_shape'));
        $this->assertFalse($preview->json('settings.show_tooltips'));
    }

    public function test_disabling_widget_in_dashboard_synchronizes_to_storefront_and_preview(): void
    {
        $this->authStoreA()->put('/shopify/settings', [
            'is_enabled' => '0',
            'position' => 'bottom_left',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);

        $storefront = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $this->assertFalse($storefront->json('enabled'));
        $this->assertEmpty($storefront->json('links'));
    }

    public function test_multiple_stores_retain_separate_canonical_configurations(): void
    {
        StorefrontSetting::create([
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'bottom_left',
            'icon_size' => 'large',
            'icon_shape' => 'circle',
            'show_tooltips' => true,
        ]);

        StorefrontSetting::create([
            'shopify_store_id' => $this->storeB->id,
            'is_enabled' => true,
            'position' => 'vertical_left',
            'icon_size' => 'small',
            'icon_shape' => 'square',
            'show_tooltips' => false,
        ]);

        $resA = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $resB = $this->getJson('/api/storefront/config?shop=store-beta.myshopify.com');

        $this->assertEquals('bottom_left', $resA->json('settings.position'));
        $this->assertEquals('large', $resA->json('settings.icon_size'));
        $this->assertEquals('circle', $resA->json('settings.icon_shape'));
        $this->assertTrue($resA->json('settings.show_tooltips'));

        $this->assertEquals('vertical_left', $resB->json('settings.position'));
        $this->assertEquals('small', $resB->json('settings.icon_size'));
        $this->assertEquals('square', $resB->json('settings.icon_shape'));
        $this->assertFalse($resB->json('settings.show_tooltips'));
    }

    public function test_dashboard_toggle_off_synchronizes_enabled_false_to_storefront_and_theme_editor_preview(): void
    {
        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Disable widget in dashboard
        $response = $this->authStoreA()->put('/shopify/settings', [
            'is_enabled' => '0',
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'show_tooltips' => '1',
        ]);
        $response->assertSessionHasNoErrors();

        // 1. Live storefront receives enabled: false and empty links
        $storefront = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $storefront->assertStatus(200);
        $this->assertFalse($storefront->json('enabled'));
        $this->assertEmpty($storefront->json('links'));

        // 2. Theme Editor preview receives enabled: false and empty links (exact same canonical state)
        $preview = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com&preview=1');
        $preview->assertStatus(200);
        $this->assertFalse($preview->json('enabled'));
        $this->assertEmpty($preview->json('links'));
    }

    public function test_dashboard_toggle_on_synchronizes_enabled_true_to_storefront_and_theme_editor_preview(): void
    {
        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Enable widget in dashboard
        $response = $this->authStoreA()->put('/shopify/settings', [
            'is_enabled' => '1',
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'show_tooltips' => '1',
        ]);
        $response->assertSessionHasNoErrors();

        // 1. Live storefront receives enabled: true and active links
        $storefront = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com');
        $storefront->assertStatus(200);
        $this->assertTrue($storefront->json('enabled'));
        $this->assertCount(1, $storefront->json('links'));

        // 2. Theme Editor preview receives enabled: true and active links
        $preview = $this->getJson('/api/storefront/config?shop=store-alpha.myshopify.com&preview=1');
        $preview->assertStatus(200);
        $this->assertTrue($preview->json('enabled'));
        $this->assertCount(1, $preview->json('links'));
    }

    public function test_theme_app_extension_schema_does_not_contain_duplicate_enable_widget_setting(): void
    {
        $liquidPath = base_path('extensions/socialnest-theme-ext/blocks/floating-social-dock.liquid');
        $this->assertFileExists($liquidPath);

        $liquidContent = file_get_contents($liquidPath);

        // Assert liquid does not contain the old block.settings.enable_widget check
        $this->assertStringNotContainsString('block.settings.enable_widget', $liquidContent);

        // Extract schema JSON
        $matched = preg_match('/\{%\s*schema\s*%\}([\s\S]*?)\{%\s*endschema\s*%\}/', $liquidContent, $matches);
        $this->assertSame(1, $matched);
        $schema = json_decode($matches[1], true);
        $this->assertIsArray($schema);

        $settingIds = array_column($schema['settings'] ?? [], 'id');
        $this->assertNotContains('enable_widget', $settingIds, 'Theme App Extension schema must NOT contain duplicate enable_widget setting.');
        $this->assertContains('app_url', $settingIds);
    }

    public function test_unauthenticated_storefront_cannot_modify_laravel_settings(): void
    {
        StorefrontSetting::create([
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'show_tooltips' => true,
        ]);

        // Unauthenticated request to update settings
        $response = $this->put('/shopify/settings', [
            'is_enabled' => '0',
            'position' => 'vertical_left',
        ]);

        // Must redirect to install or unauthorized
        $this->assertTrue($response->isRedirect() || $response->isForbidden());

        // Database value must remain unchanged
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'is_enabled' => true,
            'position' => 'bottom_right',
        ]);
    }

    public function test_settings_page_contains_sticky_storefront_preview_panel_with_all_required_elements_and_css(): void
    {
        $response = $this->authStoreA()->get('/shopify/settings');

        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Confirm polished preview elements are present and promotional/demo elements are removed
        $this->assertStringContainsString('preview-canvas-viewport', $content);
        $this->assertStringContainsString('preview-screen-frame', $content);
        $this->assertStringContainsString('browser-address-pill', $content);
        $this->assertStringContainsString('admin/themes/current/editor?context=apps', $content);

        // Verify promotional & developer-facing elements were removed
        $this->assertStringNotContainsString('Storefront Demo', $content);
        $this->assertStringNotContainsString('Interactive live preview', $content);
        $this->assertStringNotContainsString('Single Source of Truth Architecture', $content);
        $this->assertStringNotContainsString('Verify in Theme Editor', $content);

        // 2. Confirm CSS position: sticky and required layout properties are applied to the preview column
        $this->assertStringContainsString('.settings-preview-column {', $content);
        $this->assertStringContainsString('position: sticky;', $content);
        $this->assertStringContainsString('top: calc(74px + 1.25rem);', $content);
        $this->assertStringContainsString('align-self: start;', $content);
        $this->assertStringContainsString('max-height: calc(100vh - 74px - 2rem);', $content);
        $this->assertStringContainsString('overflow-y: auto;', $content);

        // 3. Confirm responsive mobile/tablet media query resets sticky behavior
        $this->assertStringContainsString('@media (max-width: 1024px)', $content);
        $this->assertStringContainsString('position: static;', $content);
    }
}
