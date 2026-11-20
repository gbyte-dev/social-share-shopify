<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Models\StorefrontSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontProductShareLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = ShopifyStore::create([
            'shop' => '26-sep.myshopify.com',
            'access_token' => 'shpat_test_token_lifecycle',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    public function test_storefront_config_returns_product_share_settings_for_store(): void
    {
        $settings = StorefrontSetting::create([
            'shopify_store_id' => $this->store->id,
            'is_enabled' => true,
            'position' => 'vertical_right',
            'product_share_enabled' => true,
            'product_share_button_text' => 'Share Product',
            'product_share_button_style' => 'solid',
            'product_share_button_background_color' => 'rgba(0, 81, 255, 1)',
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->store->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/test',
            'label' => 'Follow us',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson("/api/storefront/config?shop={$this->store->shop}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'enabled' => true,
            'settings' => [
                'product_share_enabled' => true,
                'product_share_button_text' => 'Share Product',
                'product_share_button_style' => 'solid',
                'product_share_button_background_color' => 'rgba(0, 81, 255, 1)',
                'position' => 'vertical_right',
            ],
        ]);

        $this->assertCount(1, $response->json('links'));
        $this->assertEquals('instagram', $response->json('links.0.platform'));
    }

    public function test_storefront_script_is_under_10kb_and_orchestrates_extracted_modules(): void
    {
        $mainScriptPath = base_path('extensions/socialnest-theme-ext/assets/socialnest-storefront.js');
        $this->assertFileExists($mainScriptPath);

        $fileSize = filesize($mainScriptPath);
        // Verify main file is under 10 KB (< 10,000 bytes)
        $this->assertLessThan(10000, $fileSize, "Main script file size ({$fileSize} bytes) must be under 10 KB");

        $scriptContent = file_get_contents($mainScriptPath);

        // Verify ES module imports from flat sibling files
        $this->assertStringContainsString('import { initSocialDock, renderDock, initFloatingCta } from "./socialnest-social-dock.js"', $scriptContent);
        $this->assertStringContainsString('import { createProductFeatures } from "./socialnest-product-share.js"', $scriptContent);

        // Verify fragile external dynamic import is eliminated
        $this->assertStringNotContainsString('import(new URL("socialnest-storefront-product.js"', $scriptContent);

        // Verify public API surface on window.SocialNestStorefront
        $this->assertStringContainsString('SocialNestStorefront', $scriptContent);
        $this->assertStringContainsString('initProductShare', $scriptContent);
        $this->assertStringContainsString('renderDock', $scriptContent);
        $this->assertStringContainsString('initFloatingCta', $scriptContent);
    }

    public function test_modular_product_features_file_exists_and_exports_create_product_features(): void
    {
        $productModulePath = base_path('extensions/socialnest-theme-ext/assets/socialnest-product-share.js');
        $this->assertFileExists($productModulePath);

        $content = file_get_contents($productModulePath);
        $this->assertStringContainsString('export function createProductFeatures', $content);
        $this->assertStringContainsString('function createProductFeatures', $content);
        $this->assertStringContainsString('initProductShare', $content);
        $this->assertStringContainsString('.sn-product-share-btn', $content);
        $this->assertStringContainsString('.sn-product-row-2', $content);
        $this->assertStringContainsString('sn-product-share-full', $content);
        $this->assertStringContainsString('product-form-buttons', $content);
        $this->assertStringContainsString('accelerated-checkout-block', $content);
        $this->assertStringContainsString('whatsapp', $content);
        $this->assertStringContainsString('facebook', $content);
        $this->assertStringContainsString('pinterest', $content);
        $this->assertStringContainsString('copy_link', $content);
    }

    public function test_modular_social_dock_file_exists_and_contains_dock_rendering(): void
    {
        $dockModulePath = base_path('extensions/socialnest-theme-ext/assets/socialnest-social-dock.js');
        $this->assertFileExists($dockModulePath);

        $content = file_get_contents($dockModulePath);
        $this->assertStringContainsString('export function renderDock', $content);
        $this->assertStringContainsString('export function initFloatingCta', $content);
        $this->assertStringContainsString('socialnest-dock-container', $content);
        $this->assertStringContainsString('sn-dock-item', $content);
    }

    public function test_css_includes_full_width_styling_for_stacked_share_row(): void
    {
        $cssPath = base_path('extensions/socialnest-theme-ext/assets/socialnest-storefront.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('.sn-product-row-2.sn-product-share-full>.sn-product-share-btn', $css);
        $this->assertStringContainsString('margin: 8px 0 0 !important', $css);
    }
}
