<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Models\SocialLinkClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShareButtonTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = ShopifyStore::create([
            'shop' => 'store-product-share.myshopify.com',
            'access_token' => 'shpat_test_token_share',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function authStore(ShopifyStore $store): self
    {
        return $this->withSession([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
            'shopify_host' => base64_encode($store->shop.'/admin'),
        ]);
    }

    public function test_default_product_share_settings_are_populated(): void
    {
        $settings = $this->store->getStorefrontSettings();

        $this->assertTrue($settings->product_share_enabled);
        $this->assertEquals('Share Product', $settings->product_share_button_text);
        $this->assertEquals('outline', $settings->product_share_button_style);

        $apiRes = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $apiRes->assertStatus(200);
        $this->assertTrue($apiRes->json('settings.product_share_enabled'));
        $this->assertEquals('Share Product', $apiRes->json('settings.product_share_button_text'));
        $this->assertEquals('outline', $apiRes->json('settings.product_share_button_style'));
    }

    public function test_product_share_settings_can_be_updated_and_persisted(): void
    {
        $response = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'lift',
            'product_share_enabled' => '0',
            'product_share_button_text' => 'Share with Friends',
            'product_share_button_style' => 'solid',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->store->id,
            'product_share_enabled' => false,
            'product_share_button_text' => 'Share with Friends',
            'product_share_button_style' => 'solid',
        ]);

        $apiRes = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $apiRes->assertStatus(200);
        $this->assertFalse($apiRes->json('settings.product_share_enabled'));
        $this->assertEquals('Share with Friends', $apiRes->json('settings.product_share_button_text'));
        $this->assertEquals('solid', $apiRes->json('settings.product_share_button_style'));
    }

    public function test_product_share_button_toggle_off_persists_as_false_when_omitted_from_request(): void
    {
        // First ensure settings exist and product_share_enabled is true
        $settings = $this->store->getStorefrontSettings();
        $settings->update(['product_share_enabled' => true]);
        $this->assertTrue($this->store->fresh()->storefrontSettings->product_share_enabled);

        // Submit form with product_share_enabled omitted (standard unchecked HTML checkbox behavior)
        $response = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            // product_share_enabled is intentionally omitted
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->store->id,
            'product_share_enabled' => false,
        ]);

        $apiRes = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $apiRes->assertStatus(200);
        $this->assertSame(false, $apiRes->json('settings.product_share_enabled'));
    }

    public function test_api_returns_strict_boolean_for_product_share_enabled(): void
    {
        $settings = $this->store->getStorefrontSettings();

        // 1. When false
        $settings->update(['product_share_enabled' => false]);
        $resFalse = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $resFalse->assertStatus(200);
        $rawJsonFalse = $resFalse->getContent();
        $this->assertStringContainsString('"product_share_enabled":false', str_replace(' ', '', $rawJsonFalse));
        $this->assertSame(false, $resFalse->json('settings.product_share_enabled'));

        // 2. When true
        $settings->update(['product_share_enabled' => true]);
        $resTrue = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $resTrue->assertStatus(200);
        $rawJsonTrue = $resTrue->getContent();
        $this->assertStringContainsString('"product_share_enabled":true', str_replace(' ', '', $rawJsonTrue));
        $this->assertSame(true, $resTrue->json('settings.product_share_enabled'));
    }

    public function test_product_share_button_style_validation(): void
    {
        $validStyles = ['outline', 'solid', 'minimal'];

        foreach ($validStyles as $style) {
            $response = $this->authStore($this->store)->put('/shopify/settings', [
                'position' => 'bottom_right',
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
                'product_share_button_style' => $style,
            ]);

            $response->assertSessionHasNoErrors();

            $this->assertDatabaseHas('storefront_settings', [
                'shopify_store_id' => $this->store->id,
                'product_share_button_style' => $style,
            ]);
        }

        // Test invalid style rejection
        $invalidResponse = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'product_share_button_style' => 'invalid_glow_style',
        ]);

        $invalidResponse->assertSessionHasErrors('product_share_button_style');
    }

    public function test_product_share_click_tracking_without_preexisting_dock_link(): void
    {
        $response = $this->postJson('/api/storefront/track-click', [
            'shop' => $this->store->shop,
            'platform' => 'whatsapp',
            'product_id' => 'gid://shopify/Product/987654321',
            'product_title' => 'Minimalist Linen Shirt',
            'page_url' => 'https://store-product-share.myshopify.com/products/linen-shirt?variant=123',
            'device_type' => 'mobile',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->store->id,
            'platform' => 'whatsapp',
            'product_id' => 'gid://shopify/Product/987654321',
            'product_title' => 'Minimalist Linen Shirt',
        ]);
    }

    public function test_product_share_click_tracking_deduplication(): void
    {
        // First click
        $res1 = $this->postJson('/api/storefront/track-click', [
            'shop' => $this->store->shop,
            'platform' => 'telegram',
            'product_id' => 'gid://shopify/Product/111222',
            'product_title' => 'Wool Sweater',
        ]);

        $res1->assertStatus(200);
        $res1->assertJson(['success' => true]);

        // Duplicate click immediately after
        $res2 = $this->postJson('/api/storefront/track-click', [
            'shop' => $this->store->shop,
            'platform' => 'telegram',
            'product_id' => 'gid://shopify/Product/111222',
            'product_title' => 'Wool Sweater',
        ]);

        $res2->assertStatus(200);
        $res2->assertJson(['success' => true]);

        $count = SocialLinkClick::where('shopify_store_id', $this->store->id)
            ->where('platform', 'telegram')
            ->where('product_id', 'gid://shopify/Product/111222')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_product_share_click_with_dock_link_and_product_info(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->store->id,
            'platform' => 'pinterest',
            'url' => 'https://pinterest.com/store',
            'label' => 'Pin Us',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson('/api/storefront/track-click', [
            'shop' => $this->store->shop,
            'link_id' => $link->id,
            'platform' => 'pinterest',
            'product_id' => 'gid://shopify/Product/555666',
            'product_title' => 'Handcrafted Ceramic Mug',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('social_link_clicks', [
            'social_link_id' => $link->id,
            'shopify_store_id' => $this->store->id,
            'platform' => 'pinterest',
            'product_id' => 'gid://shopify/Product/555666',
            'product_title' => 'Handcrafted Ceramic Mug',
        ]);
    }

    public function test_settings_page_renders_product_share_button_section(): void
    {
        $response = $this->authStore($this->store)->get('/shopify/settings');

        $response->assertStatus(200);
        $response->assertSee('Product Page Share Button');
        $response->assertSee('Enable Product Share Button');
        $response->assertSee('product_share_button_text');
        $response->assertSee('product_share_button_style');
        $response->assertSee('Button Background Color');
        $response->assertSee('product_share_button_background_color');
        $response->assertSee('googleColorPickerPopover');
    }

    public function test_default_product_share_button_background_color_is_populated(): void
    {
        $settings = $this->store->getStorefrontSettings();

        $this->assertEquals('rgba(17, 24, 39, 1)', $settings->product_share_button_background_color);

        $apiRes = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $apiRes->assertStatus(200);
        $this->assertEquals('rgba(17, 24, 39, 1)', $apiRes->json('settings.product_share_button_background_color'));
    }

    public function test_product_share_button_background_color_can_be_updated_and_persisted(): void
    {
        // 1. Update with Hex color
        $resHex = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'product_share_button_background_color' => '#4F46E5',
        ]);
        $resHex->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->store->id,
            'product_share_button_background_color' => '#4F46E5',
        ]);

        // 2. Update with RGBA color including transparency
        $resRgba = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'product_share_button_background_color' => 'rgba(239, 68, 68, 0.85)',
        ]);
        $resRgba->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->store->id,
            'product_share_button_background_color' => 'rgba(239, 68, 68, 0.85)',
        ]);

        // 3. Update with 8-digit Hex color
        $resHex8 = $this->authStore($this->store)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'product_share_button_background_color' => '#10B981CC',
        ]);
        $resHex8->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->store->id,
            'product_share_button_background_color' => '#10B981CC',
        ]);

        // Verify API returns updated color
        $apiRes = $this->getJson("/api/storefront/config?shop={$this->store->shop}");
        $apiRes->assertStatus(200);
        $this->assertEquals('#10B981CC', $apiRes->json('settings.product_share_button_background_color'));
    }

    public function test_product_share_button_background_color_validation_rejects_invalid_values(): void
    {
        $invalidColors = [
            'invalid-color',
            'javascript:alert(1)',
            '#gggggg',
            'rgba(300, 0, 0, 1)',
            'rgba(255, 255, 255, 2.5)',
            'rgb(1, 2)',
            '<script>',
        ];

        foreach ($invalidColors as $invalid) {
            $response = $this->authStore($this->store)->put('/shopify/settings', [
                'position' => 'bottom_right',
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
                'product_share_button_background_color' => $invalid,
            ]);

            $response->assertSessionHasErrors('product_share_button_background_color');
        }
    }
}
