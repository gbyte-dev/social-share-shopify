<?php

namespace App\Models;

use App\Services\SocialPlatformService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialLink extends Model
{
    use HasFactory;

    protected $table = 'social_links';

    protected $fillable = [
        'shopify_store_id',
        'platform',
        'platform_type',
        'custom_platform_name',
        'official_website_url',
        'original_logo_path',
        'logo_small_path',
        'logo_medium_path',
        'logo_large_path',
        'logo_source_url',
        'logo_source_domain',
        'url',
        'label',
        'alt_text',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Determine whether this link is a custom social platform.
     */
    public function isCustom(): bool
    {
        return $this->platform_type === 'custom' || $this->platform === 'other';
    }

    /**
     * Determine whether this link is a built-in platform.
     */
    public function isBuiltIn(): bool
    {
        return ! $this->isCustom();
    }

    /**
     * Get the owning Shopify store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    /**
     * Get recorded click events for this link.
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(SocialLinkClick::class, 'social_link_id');
    }

    /**
     * Get human-readable platform name.
     */
    public function getPlatformNameAttribute(): string
    {
        if ($this->isCustom() && ! empty($this->custom_platform_name)) {
            return $this->custom_platform_name;
        }

        return SocialPlatformService::name($this->platform);
    }

    /**
     * Get platform brand color.
     */
    public function getBrandColorAttribute(): string
    {
        if ($this->isCustom()) {
            if (! empty($this->custom_platform_name) && strtolower(trim($this->custom_platform_name)) === 'reddit') {
                return '#FF4500';
            }

            return '#4f46e5';
        }

        return SocialPlatformService::get($this->platform)['brand_color'] ?? '#4f46e5';
    }

    /**
     * Get SVG icon markup for the platform.
     */
    public function getIconSvgAttribute(): string
    {
        if ($this->isCustom() && ! empty($this->custom_platform_name)) {
            $customKey = strtolower(trim($this->custom_platform_name));
            if ($customKey === 'reddit') {
                return SocialPlatformService::iconSvg('reddit');
            }
        }

        return SocialPlatformService::iconSvg($this->platform);
    }

    /**
     * Get small logo URL.
     */
    public function getLogoSmallUrlAttribute(): ?string
    {
        return $this->logo_small_path ? asset('storage/'.$this->logo_small_path) : null;
    }

    /**
     * Get medium logo URL.
     */
    public function getLogoMediumUrlAttribute(): ?string
    {
        return $this->logo_medium_path ? asset('storage/'.$this->logo_medium_path) : null;
    }

    /**
     * Get large logo URL.
     */
    public function getLogoLargeUrlAttribute(): ?string
    {
        return $this->logo_large_path ? asset('storage/'.$this->logo_large_path) : null;
    }

    /**
     * Get original logo URL.
     */
    public function getOriginalLogoUrlAttribute(): ?string
    {
        return $this->original_logo_path ? asset('storage/'.$this->original_logo_path) : null;
    }

    /**
     * Get icon HTML representation (custom logo image if available, else SVG).
     */
    public function getIconHtmlAttribute(): string
    {
        if ($this->isCustom() && $this->logo_small_url) {
            $alt = htmlspecialchars($this->alt_text ?: $this->platform_name, ENT_QUOTES, 'UTF-8');

            return '<img src="'.htmlspecialchars($this->logo_small_url, ENT_QUOTES, 'UTF-8').'" alt="'.$alt.'" style="width: 22px; height: 22px; object-fit: contain; display: block;" />';
        }

        return $this->icon_svg;
    }
}
