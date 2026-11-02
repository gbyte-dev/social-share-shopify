<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\StorefrontSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoverAnimationTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = ShopifyStore::create([
            'shop' => 'store-hover-a.myshopify.com',
            'access_token' => 'shpat_test_token_a',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->storeB = ShopifyStore::create([
            'shop' => 'store-hover-b.myshopify.com',
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

    public function test_default_hover_animation_is_lift_for_backward_compatibility(): void
    {
        $settings = $this->storeA->getStorefrontSettings();
        $this->assertEquals('lift', $settings->hover_animation);

        $apiRes = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
        $apiRes->assertStatus(200);
        $this->assertEquals('lift', $apiRes->json('settings.hover_animation'));
    }

    public function test_all_six_hover_animations_can_be_selected_and_persisted(): void
    {
        $animations = ['none', 'scale', 'bounce', 'lift', 'glow', 'swing'];

        foreach ($animations as $anim) {
            $response = $this->authStore($this->storeA)->put('/shopify/settings', [
                'position' => 'bottom_right',
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
                'hover_animation' => $anim,
            ]);

            $response->assertSessionHasNoErrors();

            $this->assertDatabaseHas('storefront_settings', [
                'shopify_store_id' => $this->storeA->id,
                'hover_animation' => $anim,
            ]);

            $apiRes = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
            $apiRes->assertStatus(200);
            $this->assertEquals($anim, $apiRes->json('settings.hover_animation'));
        }
    }

    public function test_invalid_hover_animation_is_safely_rejected(): void
    {
        $invalidOptions = ['spin', 'flip', 'zoom', 'wiggle', 'explode'];

        foreach ($invalidOptions as $invalid) {
            $response = $this->authStore($this->storeA)->put('/shopify/settings', [
                'position' => 'bottom_right',
                'icon_size' => 'medium',
                'icon_shape' => 'circle',
                'hover_animation' => $invalid,
            ]);

            $response->assertSessionHasErrors('hover_animation');
        }
    }

    public function test_omitted_hover_animation_preserves_existing_configuration(): void
    {
        // First set to 'bounce'
        $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'bounce',
        ]);

        $this->assertDatabaseHas('storefront_settings', [
            'shopify_store_id' => $this->storeA->id,
            'hover_animation' => 'bounce',
        ]);

        // Submit update without hover_animation field
        $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'vertical_right',
            'icon_size' => 'large',
            'icon_shape' => 'square',
        ]);

        // Hover animation must remain 'bounce'
        $setting = StorefrontSetting::where('shopify_store_id', $this->storeA->id)->first();
        $this->assertEquals('bounce', $setting->hover_animation);
        $this->assertEquals('vertical_right', $setting->position);
    }

    public function test_store_isolation_for_hover_animation_settings(): void
    {
        $this->authStore($this->storeA)->put('/shopify/settings', [
            'position' => 'bottom_right',
            'icon_size' => 'medium',
            'icon_shape' => 'circle',
            'hover_animation' => 'glow',
        ]);

        $this->authStore($this->storeB)->put('/shopify/settings', [
            'position' => 'bottom_left',
            'icon_size' => 'small',
            'icon_shape' => 'rounded',
            'hover_animation' => 'swing',
        ]);

        $apiA = $this->getJson("/api/storefront/config?shop={$this->storeA->shop}");
        $this->assertEquals('glow', $apiA->json('settings.hover_animation'));

        $apiB = $this->getJson("/api/storefront/config?shop={$this->storeB->shop}");
        $this->assertEquals('swing', $apiB->json('settings.hover_animation'));
    }

    public function test_preview_mode_returns_default_lift_hover_animation(): void
    {
        $res = $this->getJson('/api/storefront/config?shop=uninstalled.myshopify.com&preview=1');
        $res->assertStatus(200);
        $this->assertEquals('lift', $res->json('settings.hover_animation'));
    }
}
