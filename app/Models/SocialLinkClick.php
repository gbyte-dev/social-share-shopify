<?php

namespace App\Models;

use App\Services\SocialPlatformService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialLinkClick extends Model
{
    use HasFactory;

    protected $table = 'social_link_clicks';

    public const TYPE_FLOATING_DOCK = 'floating_dock';

    public const TYPE_INLINE_SHARE = 'inline_share';

    public const TYPE_PRODUCT_SHARE = 'product_share';

    public const TYPE_FLOATING_CTA = 'floating_cta';

    protected $fillable = [
        'shopify_store_id',
        'social_link_id',
        'platform',
        'link_type',
        'product_id',
        'product_title',
        'clicked_at',
        'referrer',
        'ip_hash',
        'user_agent_hash',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SocialLinkClick $click): void {
            if ($click->clicked_at === null) {
                $click->clicked_at = now();
            }
        });
    }

    /**
     * Get the owning Shopify store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    /**
     * Get the associated social link.
     */
    public function socialLink(): BelongsTo
    {
        return $this->belongsTo(SocialLink::class, 'social_link_id');
    }

    /**
     * Get human-readable platform name.
     */
    public function getPlatformNameAttribute(): string
    {
        return SocialPlatformService::name($this->platform);
    }

    /**
     * Scope for a specific Shopify store.
     */
    public function scopeForStore($query, int $storeId)
    {
        return $query->where('shopify_store_id', $storeId);
    }

    /**
     * Scope for standard social links (dock & inline), excluding product shares.
     */
    public function scopeSocialLinksOnly($query)
    {
        return $query->whereIn('link_type', [self::TYPE_FLOATING_DOCK, self::TYPE_INLINE_SHARE]);
    }

    /**
     * Scope for product share clicks only.
     */
    public function scopeProductSharesOnly($query)
    {
        return $query->where('link_type', self::TYPE_PRODUCT_SHARE);
    }

    /**
     * Scope for a specific link type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('link_type', $type);
    }

    /**
     * Scope for a date range based on clicked_at (falling back to created_at if clicked_at is null).
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('clicked_at', [$startDate, $endDate])
                ->orWhere(function ($fallbackQ) use ($startDate, $endDate) {
                    $fallbackQ->whereNull('clicked_at')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                });
        });
    }
}
