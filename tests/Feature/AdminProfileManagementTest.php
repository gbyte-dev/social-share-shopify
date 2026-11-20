<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected string $initialPassword = 'OldPassword123!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'name' => 'Original Admin Name',
            'email' => 'admin@socialnest.test',
            'password' => Hash::make($this->initialPassword),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_app_settings_page(): void
    {
        $response = $this->get(route('admin.settings.app'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_can_view_app_settings_page(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.app'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.app');
        $response->assertSee('App Settings');
        $response->assertSee('Profile Information');
        $response->assertSee('Change Password');
        $response->assertSee($this->admin->name);
        $response->assertSee($this->admin->email);
        $response->assertSee('readonly', false);
        $response->assertSee('settings-grid', false);
        $response->assertSee('id="profile-card"', false);
        $response->assertSee('id="password-card"', false);
    }

    public function test_admin_can_update_their_own_name(): void
    {
        $newName = 'Updated Super Admin';

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.profile'), [
                'name' => $newName,
            ]);

        $response->assertRedirect(route('admin.settings.app'));
        $response->assertSessionHas('profile_success', 'Profile information updated successfully.');

        $this->admin->refresh();
        $this->assertSame($newName, $this->admin->name);
        $this->assertSame('admin@socialnest.test', $this->admin->email);
    }

    public function test_invalid_name_is_rejected(): void
    {
        // 1. Missing / empty name
        $responseEmpty = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.app'))
            ->put(route('admin.settings.app.profile'), [
                'name' => '',
            ]);

        $responseEmpty->assertRedirect(route('admin.settings.app'));
        $responseEmpty->assertSessionHasErrors(['name']);

        // 2. Excessively long name (> 255 chars)
        $responseTooLong = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.app'))
            ->put(route('admin.settings.app.profile'), [
                'name' => str_repeat('A', 256),
            ]);

        $responseTooLong->assertRedirect(route('admin.settings.app'));
        $responseTooLong->assertSessionHasErrors(['name']);

        // Database value remains untouched
        $this->admin->refresh();
        $this->assertSame('Original Admin Name', $this->admin->name);
    }

    public function test_admin_cannot_update_another_admin_profile_via_request_parameters(): void
    {
        $otherAdmin = Admin::factory()->create([
            'name' => 'Other Administrator',
            'email' => 'other@socialnest.test',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.profile'), [
                'id' => $otherAdmin->id,
                'user_id' => $otherAdmin->id,
                'email' => 'hacked@socialnest.test',
                'name' => 'Attempted Spoof Name',
            ]);

        $response->assertRedirect(route('admin.settings.app'));

        // Target admin should be updated
        $this->admin->refresh();
        $this->assertSame('Attempted Spoof Name', $this->admin->name);

        // Other admin remains completely unmodified
        $otherAdmin->refresh();
        $this->assertSame('Other Administrator', $otherAdmin->name);
        $this->assertSame('other@socialnest.test', $otherAdmin->email);
    }

    public function test_unauthenticated_users_cannot_update_admin_details(): void
    {
        $response = $this->put(route('admin.settings.app.profile'), [
            'name' => 'Unauthorized Name Change',
        ]);

        $response->assertRedirect(route('admin.login'));

        $this->admin->refresh();
        $this->assertSame('Original Admin Name', $this->admin->name);
    }

    public function test_correct_current_password_allows_password_change(): void
    {
        $newPassword = 'BrandNewPassword890!';

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.password'), [
                'current_password' => $this->initialPassword,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertRedirect(route('admin.settings.app'));
        $response->assertSessionHas('password_success', 'Password updated successfully.');

        $this->admin->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->admin->password));
    }

    public function test_incorrect_current_password_is_rejected(): void
    {
        $newPassword = 'BrandNewPassword890!';

        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.app'))
            ->put(route('admin.settings.app.password'), [
                'current_password' => 'WrongPassword123!',
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertRedirect(route('admin.settings.app'));
        $response->assertSessionHasErrors(['current_password']);

        // Password hash in DB must NOT change
        $this->admin->refresh();
        $this->assertTrue(Hash::check($this->initialPassword, $this->admin->password));
        $this->assertFalse(Hash::check($newPassword, $this->admin->password));
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.app'))
            ->put(route('admin.settings.app.password'), [
                'current_password' => $this->initialPassword,
                'password' => 'BrandNewPassword890!',
                'password_confirmation' => 'DifferentMismatchPassword999!',
            ]);

        $response->assertRedirect(route('admin.settings.app'));
        $response->assertSessionHasErrors(['password']);

        $this->admin->refresh();
        $this->assertTrue(Hash::check($this->initialPassword, $this->admin->password));
    }

    public function test_new_password_is_stored_as_a_hash(): void
    {
        $newPassword = 'ComplexPassword789!';

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.password'), [
                'current_password' => $this->initialPassword,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $this->admin->refresh();

        // Must never store plain-text
        $this->assertNotSame($newPassword, $this->admin->password);
        $this->assertStringStartsWith('$', $this->admin->password);
        $this->assertTrue(Hash::check($newPassword, $this->admin->password));
    }

    public function test_old_password_no_longer_authenticates(): void
    {
        $newPassword = 'BrandNewPassword890!';

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.password'), [
                'current_password' => $this->initialPassword,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $oldPasswordAuthAttempt = Auth::guard('admin')->attempt([
            'email' => $this->admin->email,
            'password' => $this->initialPassword,
        ]);

        $this->assertFalse($oldPasswordAuthAttempt);
    }

    public function test_new_password_authenticates_successfully(): void
    {
        $newPassword = 'BrandNewPassword890!';

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.password'), [
                'current_password' => $this->initialPassword,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $newPasswordAuthAttempt = Auth::guard('admin')->attempt([
            'email' => $this->admin->email,
            'password' => $newPassword,
        ]);

        $this->assertTrue($newPasswordAuthAttempt);
        $this->assertSame($this->admin->id, Auth::guard('admin')->id());
    }

    public function test_unauthenticated_users_cannot_change_passwords(): void
    {
        $response = $this->put(route('admin.settings.app.password'), [
            'current_password' => $this->initialPassword,
            'password' => 'HackerNewPassword123!',
            'password_confirmation' => 'HackerNewPassword123!',
        ]);

        $response->assertRedirect(route('admin.login'));

        $this->admin->refresh();
        $this->assertTrue(Hash::check($this->initialPassword, $this->admin->password));
    }

    public function test_admin_session_remains_authenticated_after_password_change(): void
    {
        $newPassword = 'BrandNewPassword890!';

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.app.password'), [
                'current_password' => $this->initialPassword,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertRedirect(route('admin.settings.app'));
        $this->assertAuthenticatedAs($this->admin, 'admin');
    }
}
