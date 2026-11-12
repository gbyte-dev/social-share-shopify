<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorefrontSettingsController extends Controller
{
    /**
     * Display the storefront settings customization page.
     */
    public function index(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $settings = $store->getStorefrontSettings();
        $links = $store->socialLinks()->where('is_active', true)->get();

        return view('shopify.settings.index', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'settings' => $settings,
            'activeLinks' => $links,
        ]);
    }

    /**
     * Update the storefront widget configuration.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $validated = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'string', Rule::in(['bottom_right', 'bottom_left', 'vertical_right', 'vertical_left', 'custom'])],
            'custom_horizontal_align' => ['nullable', 'string', Rule::in(['left', 'center', 'right'])],
            'custom_vertical_align' => ['nullable', 'string', Rule::in(['top', 'center', 'bottom'])],
            'custom_offset_x_desktop' => ['nullable', 'integer', 'min:0', 'max:200'],
            'custom_offset_y_desktop' => ['nullable', 'integer', 'min:0', 'max:200'],
            'custom_offset_x_mobile' => ['nullable', 'integer', 'min:0', 'max:200'],
            'custom_offset_y_mobile' => ['nullable', 'integer', 'min:0', 'max:200'],
            'icon_size' => ['sometimes', 'string', Rule::in(['small', 'medium', 'large'])],
            'icon_shape' => ['sometimes', 'string', Rule::in(['circle', 'rounded', 'square'])],
            'hover_animation' => ['sometimes', 'nullable', 'string', Rule::in(['none', 'scale', 'bounce', 'lift', 'glow', 'swing'])],
            'show_tooltips' => ['sometimes', 'boolean'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'product_share_enabled' => ['sometimes', 'boolean'],
            'product_share_button_text' => ['nullable', 'string', 'max:50'],
            'product_share_button_style' => ['nullable', 'string', Rule::in(['outline', 'solid', 'minimal'])],
            'product_share_button_background_color' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && ! self::isValidColor($value)) {
                        $fail('The '.$attribute.' must be a valid HEX or RGBA color format.');
                    }
                },
            ],
            // Floating Custom CTA validation rules
            'floating_cta_enabled' => ['sometimes', 'boolean'],
            'floating_cta_app_name' => ['nullable', 'string', 'max:100'],
            'floating_cta_button_text' => ['nullable', 'string', 'max:100'],
            'floating_cta_destination_url' => [
                'nullable',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && ! self::isValidDestinationUrl($value)) {
                        $fail('The '.$attribute.' must be a valid HTTP or HTTPS URL.');
                    }
                },
            ],
            'floating_cta_icon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_floating_cta_icon' => ['sometimes', 'boolean'],
            'floating_cta_show_icon' => ['sometimes', 'boolean'],
            'floating_cta_background_color' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && ! self::isValidColor($value)) {
                        $fail('The '.$attribute.' must be a valid HEX or RGBA color format.');
                    }
                },
            ],
            'floating_cta_text_color' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && ! self::isValidColor($value)) {
                        $fail('The '.$attribute.' must be a valid HEX or RGBA color format.');
                    }
                },
            ],
            'floating_cta_button_size' => ['nullable', 'string', Rule::in(['compact', 'medium', 'large'])],
            'floating_cta_desktop_enabled' => ['sometimes', 'boolean'],
            'floating_cta_mobile_enabled' => ['sometimes', 'boolean'],
        ]);

        $settings = $store->getStorefrontSettings();

        $customHAlign = $validated['custom_horizontal_align'] ?? ($settings->custom_horizontal_align ?? 'right');
        $customVAlign = $validated['custom_vertical_align'] ?? ($settings->custom_vertical_align ?? 'bottom');
        $offsetXDesktop = isset($validated['custom_offset_x_desktop']) && $validated['custom_offset_x_desktop'] !== ''
            ? (int) $validated['custom_offset_x_desktop']
            : ($settings->custom_offset_x_desktop ?? 24);
        $offsetYDesktop = isset($validated['custom_offset_y_desktop']) && $validated['custom_offset_y_desktop'] !== ''
            ? (int) $validated['custom_offset_y_desktop']
            : ($settings->custom_offset_y_desktop ?? 24);
        $offsetXMobile = isset($validated['custom_offset_x_mobile']) && $validated['custom_offset_x_mobile'] !== ''
            ? (int) $validated['custom_offset_x_mobile']
            : ($settings->custom_offset_x_mobile ?? 16);
        $offsetYMobile = isset($validated['custom_offset_y_mobile']) && $validated['custom_offset_y_mobile'] !== ''
            ? (int) $validated['custom_offset_y_mobile']
            : ($settings->custom_offset_y_mobile ?? 16);
        $hoverAnimation = ! empty($validated['hover_animation'])
            ? $validated['hover_animation']
            : ($settings->hover_animation ?? 'lift');
        $productShareEnabled = $request->has('product_share_enabled')
            ? $request->boolean('product_share_enabled')
            : (bool) $settings->product_share_enabled;
        $productShareButtonText = ! empty($validated['product_share_button_text'])
            ? $validated['product_share_button_text']
            : ($settings->product_share_button_text ?? 'Share Product');
        $productShareButtonStyle = ! empty($validated['product_share_button_style'])
            ? $validated['product_share_button_style']
            : ($settings->product_share_button_style ?? 'outline');
        $productShareButtonBgColor = ! empty($validated['product_share_button_background_color'])
            ? $validated['product_share_button_background_color']
            : ($settings->product_share_button_background_color ?? 'rgba(17, 24, 39, 1)');

        // Handle Floating CTA icon file upload, replacement, or removal
        $iconPath = $settings->floating_cta_icon_path;
        if ($request->boolean('remove_floating_cta_icon')) {
            if ($iconPath && Storage::disk('public')->exists($iconPath)) {
                Storage::disk('public')->delete($iconPath);
            }
            $iconPath = null;
        } elseif ($request->hasFile('floating_cta_icon')) {
            $file = $request->file('floating_cta_icon');
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'svg') {
                $rawSvg = file_get_contents($file->getRealPath());
                if (! self::sanitizeSvgContent((string) $rawSvg)) {
                    throw ValidationException::withMessages([
                        'floating_cta_icon' => 'The uploaded SVG icon contains potentially unsafe scripts or elements.',
                    ]);
                }
            }
            if ($iconPath && Storage::disk('public')->exists($iconPath)) {
                Storage::disk('public')->delete($iconPath);
            }
            $iconPath = $file->store('cta_icons', 'public');
        }

        $floatingCtaEnabled = $request->has('floating_cta_enabled')
            ? $request->boolean('floating_cta_enabled')
            : (bool) $settings->floating_cta_enabled;
        $floatingCtaAppName = array_key_exists('floating_cta_app_name', $validated) ? trim((string) $validated['floating_cta_app_name']) : $settings->floating_cta_app_name;
        $floatingCtaBtnText = array_key_exists('floating_cta_button_text', $validated)
            ? (trim((string) $validated['floating_cta_button_text']) !== '' ? trim((string) $validated['floating_cta_button_text']) : ($settings->floating_cta_button_text ?? 'Get Started'))
            : ($settings->floating_cta_button_text ?? 'Get Started');
        $floatingCtaDestUrl = array_key_exists('floating_cta_destination_url', $validated)
            ? ($validated['floating_cta_destination_url'] !== null && $validated['floating_cta_destination_url'] !== '' ? trim($validated['floating_cta_destination_url']) : null)
            : $settings->floating_cta_destination_url;
        $floatingCtaShowIcon = $request->has('floating_cta_show_icon')
            ? $request->boolean('floating_cta_show_icon')
            : ($settings->floating_cta_show_icon ?? true);
        $floatingCtaBgColor = ! empty($validated['floating_cta_background_color'])
            ? $validated['floating_cta_background_color']
            : ($settings->floating_cta_background_color ?? 'rgba(17, 24, 39, 1)');
        $floatingCtaTextColor = ! empty($validated['floating_cta_text_color'])
            ? $validated['floating_cta_text_color']
            : ($settings->floating_cta_text_color ?? 'rgba(255, 255, 255, 1)');
        $floatingCtaSize = ! empty($validated['floating_cta_button_size'])
            ? $validated['floating_cta_button_size']
            : ($settings->floating_cta_button_size ?? 'medium');
        $floatingCtaDesktop = $request->has('floating_cta_desktop_enabled')
            ? $request->boolean('floating_cta_desktop_enabled')
            : ($settings->floating_cta_desktop_enabled ?? true);
        $floatingCtaMobile = $request->has('floating_cta_mobile_enabled')
            ? $request->boolean('floating_cta_mobile_enabled')
            : ($settings->floating_cta_mobile_enabled ?? true);

        $updates = [
            'is_enabled' => $request->has('is_enabled') ? $request->boolean('is_enabled') : (bool) $settings->is_enabled,
            'position' => $validated['position'] ?? $settings->position,
            'custom_horizontal_align' => $customHAlign,
            'custom_vertical_align' => $customVAlign,
            'custom_offset_x_desktop' => $offsetXDesktop,
            'custom_offset_y_desktop' => $offsetYDesktop,
            'custom_offset_x_mobile' => $offsetXMobile,
            'custom_offset_y_mobile' => $offsetYMobile,
            'icon_size' => $validated['icon_size'] ?? $settings->icon_size,
            'icon_shape' => $validated['icon_shape'] ?? $settings->icon_shape,
            'hover_animation' => $hoverAnimation,
            'show_tooltips' => $request->has('show_tooltips') ? $request->boolean('show_tooltips') : (bool) $settings->show_tooltips,
            'whatsapp_message' => array_key_exists('whatsapp_message', $validated) ? $validated['whatsapp_message'] : $settings->whatsapp_message,
            'product_share_enabled' => $productShareEnabled,
            'product_share_button_text' => $productShareButtonText,
            'product_share_button_style' => $productShareButtonStyle,
            'product_share_button_background_color' => $productShareButtonBgColor,
            'floating_cta_enabled' => $floatingCtaEnabled,
            'floating_cta_app_name' => $floatingCtaAppName,
            'floating_cta_button_text' => $floatingCtaBtnText,
            'floating_cta_destination_url' => $floatingCtaDestUrl,
            'floating_cta_icon_path' => $iconPath,
            'floating_cta_show_icon' => $floatingCtaShowIcon,
            'floating_cta_background_color' => $floatingCtaBgColor,
            'floating_cta_text_color' => $floatingCtaTextColor,
            'floating_cta_button_size' => $floatingCtaSize,
            'floating_cta_desktop_enabled' => $floatingCtaDesktop,
            'floating_cta_mobile_enabled' => $floatingCtaMobile,
        ];

        // A tab submits only its own fields. Preserve every omitted setting to prevent cross-tab resets.
        $settings->update($updates);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Storefront settings saved successfully!',
                'settings' => $settings->fresh(),
            ]);
        }

        return redirect()->route('shopify.settings.index', $this->redirectParams($request))
            ->with('success', 'Storefront settings saved successfully!');
    }

    /**
     * Build parameters to preserve Shopify embedded launch context during redirects.
     *
     * @return array<string, string>
     */
    protected function redirectParams(Request $request): array
    {
        $params = [];
        $host = $request->query('host') ?? $request->input('host');
        if ($host) {
            $params['host'] = $host;
            $shop = $request->query('shop') ?? $request->input('shop');
            if ($shop) {
                $params['shop'] = $shop;
            }
        }

        return $params;
    }

    /**
     * Resolve and strictly verify the authenticated Shopify store.
     */
    protected function resolveStore(Request $request): ShopifyStore
    {
        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store || ! $store->isInstalled()) {
            $sessionShop = session('shopify_shop');
            $sessionStoreId = session('shopify_store_id');

            if ($sessionShop && $sessionStoreId) {
                $store = ShopifyStore::where('shop', $sessionShop)
                    ->where('id', $sessionStoreId)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();
            }

            if (! $store || ! $store->isInstalled()) {
                throw new HttpResponseException(
                    redirect()->route('shopify.install')->with('error', 'Authentication required.')
                );
            }
        }

        return $store;
    }

    /**
     * Validate whether a color is a valid HEX or RGB/RGBA format.
     */
    public static function isValidColor(?string $color): bool
    {
        if (empty($color)) {
            return false;
        }

        $color = trim($color);

        // HEX formats: #RGB, #RGBA, #RRGGBB, #RRGGBBAA
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return true;
        }

        // RGBA / RGB formats: rgb(r, g, b) or rgba(r, g, b, a)
        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|1|0?\.\d+|\d{1,3}%))?\s*\)$/i', $color, $matches)) {
            $r = (int) $matches[1];
            $g = (int) $matches[2];
            $b = (int) $matches[3];

            if ($r > 255 || $g > 255 || $b > 255) {
                return false;
            }

            if (isset($matches[4])) {
                if (str_ends_with($matches[4], '%')) {
                    $pct = (float) rtrim($matches[4], '%');
                    if ($pct < 0 || $pct > 100) {
                        return false;
                    }
                } else {
                    $alpha = (float) $matches[4];
                    if ($alpha < 0 || $alpha > 1) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Validate whether a destination URL is a valid HTTP or HTTPS URL.
     * Strictly rejects unsafe protocols like javascript:, data:, file:, etc.
     */
    public static function isValidDestinationUrl(?string $url): bool
    {
        if (empty($url)) {
            return true;
        }

        $url = trim($url);

        // Reject dangerous protocol injection
        if (preg_match('/^\s*(javascript|data|file|vbscript|about):/i', $url)) {
            return false;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true);
    }

    /**
     * Sanitize SVG file contents to prevent stored XSS attacks.
     */
    public static function sanitizeSvgContent(string $svg): bool
    {
        $lowercase = strtolower($svg);

        $dangerous = [
            '<script',
            'javascript:',
            'onload',
            'onerror',
            'onclick',
            'onmouseover',
            'onfocus',
            'onblur',
            '<!doctype',
            '<!entity',
            '<iframe',
            '<object',
            '<embed',
            '<foreignobject',
        ];

        foreach ($dangerous as $term) {
            if (str_contains($lowercase, $term)) {
                return false;
            }
        }

        return str_contains($lowercase, '<svg');
    }
}
