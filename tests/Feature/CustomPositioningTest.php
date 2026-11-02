<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\StorefrontSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPositioningTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = ShopifyStore::create([
            'shop' => 'store-custom-pos-a.myshopify.com',
            'access_token' => 'shpat_test_token_a',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->storeB = ShopifyStore::create([
            'shop' => 'store-custom-pos-b.myshopify.com',
            'access_token' => 'shpat_test_token_b',
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

    public function test_all_four_existing_positions_remain_fully_functional(): void
    {
        $existingPositions = ['bottom_right', 'bottom_left', 'vertical_right', 'vertical_left'];

        foreach ($existingPositions as $pos) {
            $response = $this->authStore($this->storeA)->put('/shopify/settings', [
                'position' => $pos,
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
            ]);

            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('storefront_settings', [
                'shopify_store_id' => $this->storeA->id,
                'position' => $pos,
            ]);

            // Storefront API check
            $apiRes = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
            $apiRes->assertStatus(200);
            $this->assertEquals($pos, $apiRes->json('settings.position'));
        }
    }

    public function test_custom_position_option_can_be_saved(): void
    {
        $response = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'right',
            'custom_vertical_align' => 'bottom',
            'custom_offset_x_desktop' => 35,
            'custom_offset_y_desktop' => 45,
            'custom_offset_x_mobile' => 20,
            'custom_offset_y_mobile' => 25,
            'icon_size' => 'large',
            'icon_shape' => 'rounded',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'position' => 'custom',
            'custom_horizontal_align' => 'right',
            'custom_vertical_align' => 'bottom',
            'custom_offset_x_desktop' => 35,
            'custom_offset_y_desktop' => 45,
            'custom_offset_x_mobile' => 20,
            'custom_offset_y_mobile' => 25,
            'icon_size' => 'large',
            'icon_shape' => 'rounded',
        ]);
    }

    public function test_all_nine_alignment_combinations_persist_correctly(): void
    {
        $horizontalAlignments = ['left', 'center', 'right'];
        $verticalAlignments = ['top', 'center', 'bottom'];

        foreach ($horizontalAlignments as $hAlign) {
            foreach ($verticalAlignments as $vAlign) {
                $response = $this->authStore($this->storeA)->put('/shopify/settings', [
                    'position' => 'custom',
                    'custom_horizontal_align' => $hAlign,
                    'custom_vertical_align' => $vAlign,
                    'custom_offset_x_desktop' => 50,
                    'custom_offset_y_desktop' => 60,
                    'icon_size' => 'medium',
                    'icon_shape' => 'circle',
                ]);

                $response->assertSessionHasNoErrors();

                $this->assertDatabaseHas('storefront_settings', [
                    'shopify_store_id' => $this->storeA->id,
                    'position' => 'custom',
                    'custom_horizontal_align' => $hAlign,
                    'custom_vertical_align' => $vAlign,
                ]);

                $apiRes = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
                $this->assertEquals($hAlign, $apiRes->json('settings.custom_horizontal_align'));
                $this->assertEquals($vAlign, $apiRes->json('settings.custom_vertical_align'));
            }
        }
    }

    public function test_desktop_and_mobile_offsets_saved_independently(): void
    {
        $response = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'left',
            'custom_vertical_align' => 'top',
            'custom_offset_x_desktop' => 120,
            'custom_offset_y_desktop' => 150,
            'custom_offset_x_mobile' => 15,
            'custom_offset_y_mobile' => 25,
            'icon_size' => 'small',
            'icon_shape' => 'square',
        ]);

        $response->assertSessionHasNoErrors();

        $setting = StorefrontSetting::where('shopify_store_id', $this->storeA->id)->first();
        $this->assertEquals(120, $setting->custom_offset_x_desktop);
        $this->assertEquals(150, $setting->custom_offset_y_desktop);
        $this->assertEquals(15, $setting->custom_offset_x_mobile);
        $this->assertEquals(25, $setting->custom_offset_y_mobile);
    }

    public function test_mobile_offsets_fallback_to_sensible_defaults_when_empty(): void
    {
        $response = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'right',
            'custom_vertical_align' => 'bottom',
            'custom_offset_x_desktop' => 80,
            'custom_offset_y_desktop' => 90,
            'custom_offset_x_mobile' => '',
            'custom_offset_y_mobile' => '',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);

        $response->assertSessionHasNoErrors();

        $setting = StorefrontSetting::where('shopify_store_id', $this->storeA->id)->first();
        $this->assertTrue($setting->isCustomPosition());
        $this->assertEquals(80, $setting->custom_offset_x_desktop);
        $this->assertEquals(90, $setting->custom_offset_y_desktop);

        // Effective mobile offset helper test
        $this->assertNotNull($setting->getEffectiveMobileOffsetX());
        $this->assertNotNull($setting->getEffectiveMobileOffsetY());
    }

    public function test_offset_validation_rejects_negative_and_out_of_range_values(): void
    {
        // Negative desktop X
        $res1 = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_offset_x_desktop' => -5,
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
        $res1->assertSessionHasErrors('custom_offset_x_desktop');

        // Exceeding 200px limit
        $res2 = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_offset_y_desktop' => 201,
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
        $res2->assertSessionHasErrors('custom_offset_y_desktop');

        // Negative mobile offset
        $res3 = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_offset_x_mobile' => -10,
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
        $res3->assertSessionHasErrors('custom_offset_x_mobile');

        // Exceeding mobile offset
        $res4 = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_offset_y_mobile' => 250,
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
        $res4->assertSessionHasErrors('custom_offset_y_mobile');
    }

    public function test_invalid_alignment_values_rejected(): void
    {
        $res1 = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'invalid_leftish',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
        $res1->assertSessionHasErrors('custom_horizontal_align');

        $res2 = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_vertical_align' => 'invalid_uppish',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);
        $res2->assertSessionHasErrors('custom_vertical_align');
    }

    public function test_store_isolation_for_custom_position_settings(): void
    {
        // Store A configures custom top-left
        $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'left',
            'custom_vertical_align' => 'top',
            'custom_offset_x_desktop' => 40,
            'custom_offset_y_desktop' => 40,
            'icon_size' => 'small',
            'icon_shape' => 'square',
        ]);

        // Store B configures standard bottom-left
        $this->authStore($this->storeB)->put('/shopify/settings', [
            'position' => 'bottom_left',
            'icon_size' => 'large',
            'icon_shape' => 'circle',
        ]);

        // Store A settings remain custom top-left
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'position' => 'custom',
            'custom_horizontal_align' => 'left',
            'custom_vertical_align' => 'top',
        ]);

        // Store B settings remain standard bottom-left
        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeB->id,
            'position' => 'bottom_left',
        ]);
    }

    public function test_storefront_config_api_returns_clean_custom_position_data(): void
    {
        $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'center',
            'custom_vertical_align' => 'bottom',
            'custom_offset_x_desktop' => 70,
            'custom_offset_y_desktop' => 85,
            'custom_offset_x_mobile' => 30,
            'custom_offset_y_mobile' => 40,
            'icon_size' => 'medium',
            'icon_shape' => 'rounded',
        ]);

        $response = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
        $response->assertStatus(200);

        $settings = $response->json('settings');
        $this->assertEquals('custom', $settings['position']);
        $this->assertEquals('center', $settings['custom_horizontal_align']);
        $this->assertEquals('bottom', $settings['custom_vertical_align']);
        $this->assertEquals(70, $settings['custom_offset_x_desktop']);
        $this->assertEquals(85, $settings['custom_offset_y_desktop']);
        $this->assertEquals(30, $settings['custom_offset_x_mobile']);
        $this->assertEquals(40, $settings['custom_offset_y_mobile']);
    }

    public function test_storefront_config_preview_mode_returns_custom_defaults(): void
    {
        $response = $this->getJson('/api/storefront/config?shop=uninstalled-store.myshopify.com&preview=1');
        $response->assertStatus(200);

        $settings = $response->json('settings');
        $this->assertArrayHasKey('custom_horizontal_align', $settings);
        $this->assertArrayHasKey('custom_vertical_align', $settings);
        $this->assertArrayHasKey('custom_offset_x_desktop', $settings);
        $this->assertArrayHasKey('custom_offset_y_desktop', $settings);
        $this->assertArrayHasKey('custom_offset_x_mobile', $settings);
        $this->assertArrayHasKey('custom_offset_y_mobile', $settings);
    }

    public function test_custom_position_centered_bottom_with_zero_offsets(): void
    {
        $response = $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'custom',
            'custom_horizontal_align' => 'center',
            'custom_vertical_align' => 'bottom',
            'custom_offset_x_desktop' => 0,
            'custom_offset_y_desktop' => 0,
            'custom_offset_x_mobile' => 0,
            'custom_offset_y_mobile' => 0,
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'position' => 'custom',
            'custom_horizontal_align' => 'center',
            'custom_vertical_align' => 'bottom',
            'custom_offset_x_desktop' => 0,
            'custom_offset_y_desktop' => 0,
            'custom_offset_x_mobile' => 0,
            'custom_offset_y_mobile' => 0,
        ]);

        $apiRes = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
        $apiRes->assertStatus(200);

        $settings = $apiRes->json('settings');
        $this->assertEquals('custom', $settings['position']);
        $this->assertEquals('center', $settings['custom_horizontal_align']);
        $this->assertEquals('bottom', $settings['custom_vertical_align']);
        $this->assertSame(0, $settings['custom_offset_x_desktop']);
        $this->assertSame(0, $settings['custom_offset_y_desktop']);
        $this->assertSame(0, $settings['custom_offset_x_mobile']);
        $this->assertSame(0, $settings['custom_offset_y_mobile']);
    }
}
