<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Models\SocialLinkClick;
use App\Models\StorefrontSetting;
use App\Services\ShopifyService;
use App\Services\SocialPlatformService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontApiController extends Controller
{
    public function __construct(
        protected ShopifyService $shopifyService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Common CORS headers for public storefront APIs.
     *
     * @return array<string, string>
     */
    protected function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Shopify-Shop-Domain, Accept, Authorization',
            'Access-Control-Allow-Private-Network' => 'true',
            'Access-Control-Max-Age' => '86400',
        ];
    }

    /**
     * Format full storefront visual and CTA settings.
     *
     * @return array<string, mixed>
     */
    protected function formatStorefrontSettings(StorefrontSetting $settings): array
    {
        return [
            'position' => $settings->position,
            'custom_horizontal_align' => $settings->custom_horizontal_align ?? 'right',
            'custom_vertical_align' => $settings->custom_vertical_align ?? 'bottom',
            'custom_offset_x_desktop' => (int) ($settings->custom_offset_x_desktop ?? 24),
            'custom_offset_y_desktop' => (int) ($settings->custom_offset_y_desktop ?? 24),
            'custom_offset_x_mobile' => $settings->custom_offset_x_mobile !== null ? (int) $settings->custom_offset_x_mobile : 16,
            'custom_offset_y_mobile' => $settings->custom_offset_y_mobile !== null ? (int) $settings->custom_offset_y_mobile : 16,
            'icon_size' => $settings->icon_size,
            'icon_shape' => $settings->icon_shape,
            'hover_animation' => $settings->hover_animation ?? 'lift',
            'show_tooltips' => (bool) $settings->show_tooltips,
            'whatsapp_message' => $settings->whatsapp_message,
            'product_share_enabled' => (bool) ($settings->product_share_enabled ?? true),
            'product_share_button_text' => $settings->product_share_button_text ?? 'Share Product',
            'product_share_button_style' => $settings->product_share_button_style ?? 'outline',
            'product_share_button_background_color' => $settings->product_share_button_background_color ?? 'rgba(17, 24, 39, 1)',
            // Canonical Floating Custom CTA settings
            'floating_cta_enabled' => (bool) $settings->floating_cta_enabled,
            'floating_cta_app_name' => $settings->floating_cta_app_name,
            'floating_cta_button_text' => $settings->floating_cta_button_text ?: 'Get Started',
            'floating_cta_destination_url' => $settings->floating_cta_destination_url,
            'floating_cta_icon_url' => $settings->getFloatingCtaIconUrl(),
            'floating_cta_show_icon' => (bool) $settings->floating_cta_show_icon,
            'floating_cta_background_color' => $settings->floating_cta_background_color ?: 'rgba(17, 24, 39, 1)',
            'floating_cta_text_color' => $settings->floating_cta_text_color ?: 'rgba(255, 255, 255, 1)',
            'floating_cta_button_size' => $settings->floating_cta_button_size ?: 'medium',
            'floating_cta_desktop_enabled' => (bool) $settings->floating_cta_desktop_enabled,
            'floating_cta_mobile_enabled' => (bool) $settings->floating_cta_mobile_enabled,
            // Aliases for compatibility
            'cta_enabled' => (bool) $settings->floating_cta_enabled,
            'cta_app_name' => $settings->floating_cta_app_name,
            'cta_text' => $settings->floating_cta_button_text ?: 'Get Started',
            'cta_url' => $settings->floating_cta_destination_url,
            'cta_icon_url' => $settings->getFloatingCtaIconUrl(),
            'cta_show_icon' => (bool) $settings->floating_cta_show_icon,
            'cta_background_color' => $settings->floating_cta_background_color ?: 'rgba(17, 24, 39, 1)',
            'cta_text_color' => $settings->floating_cta_text_color ?: 'rgba(255, 255, 255, 1)',
            'cta_size' => $settings->floating_cta_button_size ?: 'medium',
            'cta_desktop_enabled' => (bool) $settings->floating_cta_desktop_enabled,
            'cta_mobile_enabled' => (bool) $settings->floating_cta_mobile_enabled,
        ];
    }

    /**
     * Provide public storefront widget configuration.
     * Only returns active social links and visual settings for the verified store.
     * Never exposes private merchant tokens, secret credentials, or internal identifiers.
     */
    public function config(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 204, $this->corsHeaders());
        }

        $rawShop = $request->query('shop') ?? $request->header('X-Shopify-Shop-Domain');

        if (empty($rawShop)) {
            return response()->json([
                'error' => 'Missing shop parameter',
            ], 400, $this->corsHeaders());
        }

        $canonicalShop = $this->shopifyService->normalizeShopDomain($rawShop);

        if (! $canonicalShop) {
            return response()->json([
                'error' => 'Invalid shop domain format',
            ], 400, $this->corsHeaders());
        }

        /** @var ShopifyStore|null $store */
        $store = ShopifyStore::where('shop', $canonicalShop)
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->first();

        if (! $store || ! $store->isInstalled()) {
            // If in preview mode (e.g. Shopify Theme Editor), return mock configuration
            if ($request->boolean('preview')) {
                return response()->json([
                    'success' => true,
                    'shop' => $canonicalShop,
                    'enabled' => true,
                    'is_preview' => true,
                    'settings' => [
                        'position' => 'bottom_right',
                        'custom_horizontal_align' => 'right',
                        'custom_vertical_align' => 'bottom',
                        'custom_offset_x_desktop' => 24,
                        'custom_offset_y_desktop' => 24,
                        'custom_offset_x_mobile' => 16,
                        'custom_offset_y_mobile' => 16,
                        'icon_size' => 'medium',
                        'icon_shape' => 'circle',
                        'hover_animation' => 'lift',
                        'show_tooltips' => true,
                        'whatsapp_message' => 'Hello! How can we help you?',
                        'product_share_enabled' => true,
                        'product_share_button_text' => 'Share Product',
                        'product_share_button_style' => 'outline',
                        'product_share_button_background_color' => 'rgba(17, 24, 39, 1)',
                        'floating_cta_enabled' => false,
                        'floating_cta_app_name' => 'SocialNest',
                        'floating_cta_button_text' => 'Get Started',
                        'floating_cta_destination_url' => 'https://'.$canonicalShop,
                        'floating_cta_icon_url' => null,
                        'floating_cta_show_icon' => true,
                        'floating_cta_background_color' => 'rgba(17, 24, 39, 1)',
                        'floating_cta_text_color' => 'rgba(255, 255, 255, 1)',
                        'floating_cta_button_size' => 'medium',
                        'floating_cta_desktop_enabled' => true,
                        'floating_cta_mobile_enabled' => true,
                        'cta_enabled' => false,
                    ],
                    'links' => [],
                ], 200, array_merge($this->corsHeaders(), [
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]));
            }

            return response()->json([
                'error' => 'Store not found or not active',
            ], 404, $this->corsHeaders());
        }

        // For shops without an active subscription, return a safe disabled configuration
        if (! $this->subscriptionService->isActive($store)) {
            return response()->json([
                'success' => true,
                'shop' => $store->shop,
                'enabled' => false,
                'subscription_active' => false,
                'settings' => [
                    'position' => 'bottom_right',
                    'icon_size' => 'medium',
                    'icon_shape' => 'circle',
                    'product_share_enabled' => false,
                    'floating_cta_enabled' => false,
                    'cta_enabled' => false,
                ],
                'links' => [],
            ], 200, array_merge($this->corsHeaders(), [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]));
        }

        $settings = $store->getStorefrontSettings();
        $formattedSettings = $this->formatStorefrontSettings($settings);

        if (! $settings->is_enabled) {
            return response()->json([
                'success' => true,
                'shop' => $store->shop,
                'enabled' => false,
                'settings' => $formattedSettings,
                'links' => [],
            ], 200, array_merge($this->corsHeaders(), [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]));
        }

        $links = $store->socialLinks()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn ($link) => [
                'id' => $link->id,
                'platform' => $link->platform,
                'platform_type' => $link->platform_type ?? ($link->isCustom() ? 'custom' : 'builtin'),
                'is_custom' => $link->isCustom(),
                'name' => $link->platform_name,
                'custom_name' => $link->isCustom() ? $link->custom_platform_name : null,
                'url' => $link->url,
                'label' => $link->label,
                'alt_text' => $link->alt_text ?: $link->platform_name,
                'brand_color' => $link->brand_color,
                'sort_order' => $link->sort_order,
                'icon_svg' => $link->icon_svg,
                'logo_small' => $link->logo_small_url,
                'logo_medium' => $link->logo_medium_url,
                'logo_large' => $link->logo_large_url,
            ]);

        return response()->json([
            'success' => true,
            'shop' => $store->shop,
            'enabled' => true,
            'settings' => $formattedSettings,
            'links' => $links,
        ], 200, array_merge($this->corsHeaders(), [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]));
    }

    /**
     * Record a social link click event from the storefront widget.
     * Uses rate-limiting and hashing to protect shopper privacy while preventing spam.
     */
    public function trackClick(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 204, $this->corsHeaders());
        }

        // Support both application/json, text/plain (from navigator.sendBeacon), and form POST
        $data = $request->all();
        if (empty($data['shop']) || empty($data['platform'])) {
            $raw = $request->getContent();
            if (! empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = array_merge($decoded, $data);
                }
            }
        }

        $validated = validator($data, [
            'shop' => ['required', 'string'],
            'link_id' => ['nullable', 'integer'],
            'platform' => ['required', 'string'],
            'link_type' => ['nullable', 'string', 'in:floating_dock,inline_share,product_share,floating_cta,cta'],
            'product_id' => ['nullable', 'string', 'max:100'],
            'product_title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $canonicalShop = $this->shopifyService->normalizeShopDomain($validated['shop']);

        if (! $canonicalShop) {
            return response()->json(['error' => 'Invalid shop'], 400, $this->corsHeaders());
        }

        $store = ShopifyStore::where('shop', $canonicalShop)
            ->where('is_active', true)
            ->first();

        if (! $store) {
            return response()->json(['error' => 'Store not found or inactive'], 404, $this->corsHeaders());
        }

        if (! $this->subscriptionService->isActive($store)) {
            return response()->json(['error' => 'Active subscription required to track events'], 403, $this->corsHeaders());
        }

        $platform = strtolower(trim($validated['platform']));
        $productId = ! empty($validated['product_id']) ? (string) $validated['product_id'] : null;
        $productTitle = ! empty($validated['product_title']) ? (string) $validated['product_title'] : null;

        // Resolve link_type: use passed link_type, or infer from context
        $linkType = $validated['link_type'] ?? null;
        if ($linkType === 'floating_cta' || $linkType === 'cta' || $platform === 'floating_cta' || $platform === 'cta') {
            $linkType = SocialLinkClick::TYPE_FLOATING_CTA;
            $platform = 'floating_cta';
        } elseif (! $linkType) {
            if ($productId !== null || $productTitle !== null || in_array($platform, ['copy_link', 'native'])) {
                $linkType = SocialLinkClick::TYPE_PRODUCT_SHARE;
            } else {
                $linkType = SocialLinkClick::TYPE_FLOATING_DOCK;
            }
        }

        $isProductShare = ($linkType === SocialLinkClick::TYPE_PRODUCT_SHARE);
        $isFloatingCta = ($linkType === SocialLinkClick::TYPE_FLOATING_CTA);

        $link = null;

        if ($isFloatingCta) {
            // Floating CTA click does not belong to an individual social link
            $link = null;
        } elseif (! empty($validated['link_id'])) {
            $link = $store->socialLinks()
                ->where('id', $validated['link_id'])
                ->where('is_active', true)
                ->first();

            if (! $link) {
                return response()->json(['error' => 'Social link not found for this store'], 404, $this->corsHeaders());
            }

            // Sync platform with the link's canonical or custom platform
            if ($link->isCustom()) {
                $customName = strtolower(trim((string) $link->custom_platform_name));
                if ($customName === 'reddit' || $platform === 'reddit') {
                    $platform = 'reddit';
                } elseif ($validated['platform'] === 'other') {
                    $platform = 'other';
                } else {
                    $platform = $customName ?: 'other';
                }
            } else {
                $platform = strtolower(trim($link->platform));
            }
        } else {
            $link = $store->socialLinks()
                ->where(function ($q) use ($platform) {
                    $q->where('platform', $platform)
                        ->orWhere('custom_platform_name', $platform);
                })
                ->where('is_active', true)
                ->first();

            if (! $link && ! $isProductShare && ! $isFloatingCta) {
                if (! SocialPlatformService::exists($platform) && ! SocialPlatformService::isCustom($platform)) {
                    return response()->json(['error' => 'Invalid platform'], 400, $this->corsHeaders());
                }

                return response()->json(['error' => 'Social link not found for this store'], 404, $this->corsHeaders());
            }

            if ($link && $link->isCustom()) {
                $customName = strtolower(trim((string) $link->custom_platform_name));
                if ($customName === 'reddit' || $platform === 'reddit') {
                    $platform = 'reddit';
                } elseif ($validated['platform'] === 'other') {
                    $platform = 'other';
                }
            }
        }

        // Anonymized hashing for privacy-safe deduplication (no raw IP or UA stored)
        $ipHash = hash('sha256', (string) $request->ip());
        $uaHash = hash('sha256', (string) $request->userAgent());

        // Duplicate click prevention within 3 seconds for the exact same IP + platform + link_type + link/product
        $recentClick = $store->clicks()
            ->where('platform', $platform)
            ->where('link_type', $linkType)
            ->where('ip_hash', $ipHash)
            ->when($link, fn ($q) => $q->where('social_link_id', $link->id))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->where(function ($q) {
                $q->where('clicked_at', '>=', now()->subSeconds(3))
                    ->orWhere(function ($q2) {
                        $q2->whereNull('clicked_at')->where('created_at', '>=', now()->subSeconds(3));
                    });
            })
            ->exists();

        if (! $recentClick) {
            $store->clicks()->create([
                'social_link_id' => $link?->id,
                'platform' => $platform,
                'link_type' => $linkType,
                'product_id' => $productId,
                'product_title' => $productTitle,
                'referrer' => $validated['referrer'] ?? null,
                'ip_hash' => $ipHash,
                'user_agent_hash' => $uaHash,
                'clicked_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
        ], 200, $this->corsHeaders());
    }
}
