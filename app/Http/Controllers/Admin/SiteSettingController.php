<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteSettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.branding', [
            'brandName' => SiteSetting::getValue('brand_name', 'Socialvire'),
            'brandLogo' => SiteSetting::getValue('brand_logo'),
            'brandFavicon' => SiteSetting::getValue('brand_favicon'),
        ]);
    }

    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],

            'brand_logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],

            'brand_favicon' => [
                'nullable',
                'file',
                'mimes:ico,png,webp',
                'max:512',
            ],
        ]);

        try {
            $directory = public_path('uploads/branding');

            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new \RuntimeException('Unable to create branding directory.');
                }
            }

            if (!is_writable($directory)) {
                throw new \RuntimeException('Branding directory is not writable.');
            }

            // Save Brand Name
            SiteSetting::updateOrCreate(
                ['key' => 'brand_name'],
                ['value' => $validated['brand_name']]
            );

            // Upload Brand Logo
            if ($request->hasFile('brand_logo')) {
                $file = $request->file('brand_logo');

                $filename = uniqid('brand_', true) . '.' . $file->extension();

                $file->move($directory, $filename);

                $oldLogo = SiteSetting::getValue('brand_logo');

                SiteSetting::updateOrCreate(
                    ['key' => 'brand_logo'],
                    ['value' => 'uploads/branding/' . $filename]
                );

                // Delete old logo after successful upload
                if ($oldLogo && str_starts_with($oldLogo, 'uploads/branding/')) {
                    $oldPath = public_path($oldLogo);

                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
            }

            // Upload Favicon
            if ($request->hasFile('brand_favicon')) {
                $file = $request->file('brand_favicon');

                $filename = uniqid('favicon_', true) . '.' . $file->extension();

                $file->move($directory, $filename);

                $oldFavicon = SiteSetting::getValue('brand_favicon');

                SiteSetting::updateOrCreate(
                    ['key' => 'brand_favicon'],
                    ['value' => 'uploads/branding/' . $filename]
                );

                // Delete old favicon after successful upload
                if ($oldFavicon && str_starts_with($oldFavicon, 'uploads/branding/')) {
                    $oldPath = public_path($oldFavicon);

                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
            }

            return back()->with(
                'success',
                'Branding settings updated successfully.'
            );

        } catch (\Throwable $e) {
            Log::error('Admin branding update failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'branding' => 'Unable to update branding. Please check server permissions.',
            ])->withInput();
        }
    }
}
