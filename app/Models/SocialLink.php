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

    public function isCustom(): bool
    {
        return $this->platform_type === 'custom' || $this->platform === 'other';
    }

    public function isBuiltIn(): bool
    {
        return ! $this->isCustom();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(SocialLinkClick::class, 'social_link_id');
    }

    public function getPlatformNameAttribute(): string
    {
        if ($this->isCustom() && ! empty($this->custom_platform_name)) {
            return $this->custom_platform_name;
        }
        return SocialPlatformService::name($this->platform);
    }

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

    // Yahan 'public/' add kiya gaya hai taaki shared hosting par URL effectively map ho sake
  public function getLogoSmallUrlAttribute(): ?string
    {
        if (! $this->logo_small_path) return null;
        
        // Agar pehle se http, https ya data URI hai toh direct return karo[cite: 19, 22]
        if (str_starts_with($this->logo_small_path, 'http://') || str_starts_with($this->logo_small_path, 'https://')) {
            return $this->logo_small_path;
        }
        
        if (str_starts_with($this->logo_small_path, 'data:image')) {
            return $this->logo_small_path;
        }

        return asset('public/' . $this->logo_small_path);
    }

    public function getLogoMediumUrlAttribute(): ?string
    {
        if (! $this->logo_medium_path) return null;

        // Agar pehle se http ya https URL hai, toh direct return karo[cite: 22]
        if (str_starts_with($this->logo_medium_path, 'http://') || str_starts_with($this->logo_medium_path, 'https://')) {
            return $this->logo_medium_path;
        }

        return asset('public/' . $this->logo_medium_path);
    }

    public function getLogoLargeUrlAttribute(): ?string
    {
        return $this->logo_large_path ? asset('public/' . $this->logo_large_path) : null;
    }

    public function getOriginalLogoUrlAttribute(): ?string
    {
        return $this->original_logo_path ? asset('public/' . $this->original_logo_path) : null;
    }

    public function getIconHtmlAttribute(): string
    {
        if ($this->isCustom() && $this->logo_small_url) {
            $alt = htmlspecialchars($this->alt_text ?: $this->platform_name, ENT_QUOTES, 'UTF-8');

            // Fallback SVG code
            $fallbackSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>';

            // Base64 encode the SVG to completely prevent HTML quote conflicts
            $base64Svg = base64_encode($fallbackSvg);

            // Replace the broken image source with the Base64 SVG cleanly
            $fallback = "this.onerror=null; this.src='data:image/svg+xml;base64," . $base64Svg . "';";

            return '<img src="' . htmlspecialchars($this->logo_small_url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" style="width: 20px; height: 20px; object-fit: contain; display: block;" onerror="' . $fallback . '" />';
        }

        return $this->icon_svg;
    }
}
