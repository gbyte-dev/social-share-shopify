<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminBrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'name' => 'Branding Admin',
            'email' => 'branding-admin@socialnest.test',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test uploads directory if created
        $testUploadDir = public_path('uploads/branding');
        if (File::isDirectory($testUploadDir)) {
            // Delete files created during tests
            foreach (File::files($testUploadDir) as $file) {
                if (str_starts_with($file->getFilename(), 'brand_') || str_starts_with($file->getFilename(), 'favicon_')) {
                    @unlink($file->getRealPath());
                }
            }
        }

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_branding_page(): void
    {
        $response = $this->get(route('admin.settings.branding'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_can_view_branding_settings_with_two_column_layout(): void
    {
        SiteSetting::updateOrCreate(['key' => 'brand_name'], ['value' => 'Socialvire Plus']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.branding'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.branding');

        // Header and core cards
        $response->assertSee('Brand Identity');
        $response->assertSee('Website Identity');
        $response->assertSee('Browser Identity');
        $response->assertSee('Branding Preview');

        // Two-column layout elements
        $response->assertSee('branding-grid', false);
        $response->assertSee('id="website-identity-card"', false);
        $response->assertSee('id="browser-identity-card"', false);
        $response->assertSee('id="branding-preview-card"', false);

        // Inputs & Preview
        $response->assertSee('name="brand_name"', false);
        $response->assertSee('name="brand_logo"', false);
        $response->assertSee('name="brand_favicon"', false);
        $response->assertSee('Socialvire Plus');
        $response->assertSee('Save Branding');
    }

    public function test_admin_can_update_brand_name(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.branding.update'), [
                'brand_name' => 'Acme Social App',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Branding settings updated successfully.');

        $this->assertSame('Acme Social App', SiteSetting::getValue('brand_name'));
    }

    public function test_empty_brand_name_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.branding'))
            ->put(route('admin.settings.branding.update'), [
                'brand_name' => '',
            ]);

        $response->assertRedirect(route('admin.settings.branding'));
        $response->assertSessionHasErrors(['brand_name']);
    }

    public function test_admin_can_upload_brand_logo(): void
    {
        $logo = UploadedFile::fake()->image('custom-logo.png', 300, 100);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.branding.update'), [
                'brand_name' => 'Logo Test Brand',
                'brand_logo' => $logo,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $storedLogo = SiteSetting::getValue('brand_logo');
        $this->assertNotNull($storedLogo);
        $this->assertStringStartsWith('uploads/branding/brand_', $storedLogo);
        $this->assertTrue(file_exists(public_path($storedLogo)));
    }

    public function test_admin_can_upload_brand_favicon(): void
    {
        $favicon = UploadedFile::fake()->image('favicon.png', 32, 32);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.branding.update'), [
                'brand_name' => 'Favicon Test Brand',
                'brand_favicon' => $favicon,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $storedFavicon = SiteSetting::getValue('brand_favicon');
        $this->assertNotNull($storedFavicon);
        $this->assertStringStartsWith('uploads/branding/favicon_', $storedFavicon);
        $this->assertTrue(file_exists(public_path($storedFavicon)));
    }

    public function test_invalid_logo_file_type_is_rejected(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.branding'))
            ->put(route('admin.settings.branding.update'), [
                'brand_name' => 'Invalid File Test',
                'brand_logo' => $invalidFile,
            ]);

        $response->assertRedirect(route('admin.settings.branding'));
        $response->assertSessionHasErrors(['brand_logo']);
    }

    public function test_get_asset_url_resolves_valid_url_for_existing_file(): void
    {
        $file = UploadedFile::fake()->image('test_logo.png', 100, 100);
        $file->move(public_path('uploads/branding'), 'brand_test_unit.png');

        SiteSetting::updateOrCreate(['key' => 'brand_logo'], ['value' => 'uploads/branding/brand_test_unit.png']);

        $url = SiteSetting::getAssetUrl('brand_logo');
        $this->assertNotNull($url);
        $this->assertStringNotContainsString('/public/uploads/', $url);
        $this->assertStringContainsString('/uploads/branding/brand_test_unit.png', $url);
    }

    public function test_get_asset_url_returns_null_for_missing_file(): void
    {
        SiteSetting::updateOrCreate(['key' => 'brand_logo'], ['value' => 'uploads/branding/non_existent_file_xyz.png']);

        $url = SiteSetting::getAssetUrl('brand_logo');
        $this->assertNull($url);
    }

    public function test_branding_page_and_sidebar_display_graceful_fallbacks_when_files_are_missing(): void
    {
        SiteSetting::updateOrCreate(['key' => 'brand_name'], ['value' => 'Fallback Brand']);
        SiteSetting::updateOrCreate(['key' => 'brand_logo'], ['value' => 'uploads/branding/ghost_logo.png']);
        SiteSetting::updateOrCreate(['key' => 'brand_favicon'], ['value' => 'uploads/branding/ghost_favicon.png']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.branding'));

        $response->assertStatus(200);

        // Sidebar fallback
        $response->assertSee('brand-icon', false);
        $response->assertSee('F'); // Initial of Fallback Brand

        // Upload previews fallback
        $response->assertSee('No Logo');
        $response->assertSee('No Icon');

        // Never output missing file path as broken src
        $response->assertDontSee('/public/uploads/branding/ghost_logo.png', false);
    }

    public function test_branding_page_renders_valid_image_sources_when_files_exist(): void
    {
        $logo = UploadedFile::fake()->image('valid_logo.png', 200, 50);
        $logo->move(public_path('uploads/branding'), 'brand_valid_test.png');

        $favicon = UploadedFile::fake()->image('valid_favicon.png', 32, 32);
        $favicon->move(public_path('uploads/branding'), 'favicon_valid_test.png');

        SiteSetting::updateOrCreate(['key' => 'brand_name'], ['value' => 'Live Store']);
        SiteSetting::updateOrCreate(['key' => 'brand_logo'], ['value' => 'uploads/branding/brand_valid_test.png']);
        SiteSetting::updateOrCreate(['key' => 'brand_favicon'], ['value' => 'uploads/branding/favicon_valid_test.png']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.branding'));

        $response->assertStatus(200);

        // Assert URLs do not contain duplicate /public/
        $response->assertDontSee('/public/uploads/branding/', false);
        $response->assertSee('/uploads/branding/brand_valid_test.png', false);
        $response->assertSee('/uploads/branding/favicon_valid_test.png', false);
    }
}
