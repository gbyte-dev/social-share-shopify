<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    /**
     * Display the App Settings page with Admin Profile and Password forms.
     */
    public function edit(Request $request): View
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('admin.settings.app', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update the authenticated admin's profile name.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'The administrator name is required.',
            'name.string' => 'The name must be a valid text string.',
            'name.max' => 'The name cannot exceed 255 characters.',
        ]);

        $admin->forceFill([
            'name' => $validated['name'],
        ])->save();

        return redirect()
            ->route('admin.settings.app')
            ->with('profile_success', 'Profile information updated successfully.')
            ->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update the authenticated admin's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($admin): void {
                    if (! Hash::check((string) $value, (string) $admin->password)) {
                        $fail('The provided current password does not match our records.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers(),
                'confirmed',
            ],
        ], [
            'current_password.required' => 'The current password is required.',
            'password.required' => 'The new password is required.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        $admin->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return redirect()
            ->route('admin.settings.app')
            ->with('password_success', 'Password updated successfully.')
            ->with('success', 'Password updated successfully.');
    }
}
