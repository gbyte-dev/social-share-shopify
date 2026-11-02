<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontSetting extends Model
{
    use HasFactory;

    protected $table = 'storefront_settings';

    protected $fillable = [
        'shopify_store_id',
        'is_enabled',
        'position',
        'custom_horizontal_align',
        'custom_vertical_align',
        'custom_offset_x_desktop',
        'custom_offset_y_desktop',
        'custom_offset_x_mobile',
        'custom_offset_y_mobile',
        'icon_size',
        'icon_shape',
        'hover_animation',
        'show_tooltips',
        'whatsapp_message',
        'product_share_enabled',
        'product_share_button_text',
        'product_share_button_style',
        'product_share_button_background_color',
        'floating_cta_enabled',
        'floating_cta_app_name',
        'floating_cta_button_text',
        'floating_cta_destination_url',
        'floating_cta_icon_path',
        'floating_cta_show_icon',
        'floating_cta_background_color',
        'floating_cta_text_color',
        'floating_cta_button_size',
        'floating_cta_desktop_enabled',
        'floating_cta_mobile_enabled',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'product_share_button_background_color' => 'rgba(17, 24, 39, 1)',
        'floating_cta_enabled' => false,
        'floating_cta_button_text' => 'Get Started',
        'floating_cta_show_icon' => true,
        'floating_cta_background_color' => 'rgba(17, 24, 39, 1)',
        'floating_cta_text_color' => 'rgba(255, 255, 255, 1)',
        'floating_cta_button_size' => 'medium',
        'floating_cta_desktop_enabled' => true,
        'floating_cta_mobile_enabled' => true,
    ];

    /**
     * Supported hover animation types.
     *
     * @var array<string, string>
     */
    public const HOVER_ANIMATIONS = [
        'none' => 'None',
        'scale' => 'Scale',
        'bounce' => 'Bounce',
        'lift' => 'Lift',
        'glow' => 'Glow',
        'swing' => 'Swing',
    ];

    /**
     * Supported CTA button sizes.
     *
     * @var array<string, string>
     */
    public const CTA_BUTTON_SIZES = [
        'compact' => 'Compact',
        'medium' => 'Medium',
        'large' => 'Large',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'show_tooltips' => 'boolean',
            'product_share_enabled' => 'boolean',
            'custom_offset_x_desktop' => 'integer',
            'custom_offset_y_desktop' => 'integer',
            'custom_offset_x_mobile' => 'integer',
            'custom_offset_y_mobile' => 'integer',
            'floating_cta_enabled' => 'boolean',
            'floating_cta_show_icon' => 'boolean',
            'floating_cta_desktop_enabled' => 'boolean',
            'floating_cta_mobile_enabled' => 'boolean',
        ];
    }

    /**
     * Get the public URL for the uploaded floating CTA icon.
     */
    public function getFloatingCtaIconUrl(): ?string
    {
        if (empty($this->floating_cta_icon_path)) {
            return null;
        }

        return asset('storage/'.$this->floating_cta_icon_path);
    }

    /**
     * Check if custom positioning is enabled.
     */
    public function isCustomPosition(): bool
    {
        return $this->position === 'custom';
    }

    /**
     * Get effective mobile X offset with fallback to desktop or 16px sensible default.
     */
    public function getEffectiveMobileOffsetX(): int
    {
        return $this->custom_offset_x_mobile ?? $this->custom_offset_x_desktop ?? 16;
    }

    /**
     * Get effective mobile Y offset with fallback to desktop or 16px sensible default.
     */
    public function getEffectiveMobileOffsetY(): int
    {
        return $this->custom_offset_y_mobile ?? $this->custom_offset_y_desktop ?? 16;
    }

    /**
     * Get the owning Shopify store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }
}
