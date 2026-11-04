<?php

namespace App\Models;

use Database\Factories\WishlistItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class WishlistItem extends Model
{
    /** @use HasFactory<WishlistItemFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wishlist_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shop_id',
        'customer_id',
        'guest_uuid',
        'product_id',
        'variant_id',
        'product_handle',
        'product_title',
        'product_image',
        'product_price',
        'product_url',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shop_id' => 'integer',
        'customer_id' => 'string',
        'guest_uuid' => 'string',
        'product_id' => 'string',
        'variant_id' => 'string',
    ];

    /**
     * Booted method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $hasCustomer = $item->customer_id !== null && trim((string) $item->customer_id) !== '';
            $hasGuest = $item->guest_uuid !== null && trim((string) $item->guest_uuid) !== '';

            if (($hasCustomer && $hasGuest) || (! $hasCustomer && ! $hasGuest)) {
                throw new InvalidArgumentException('A wishlist item must belong to exactly one identity: either customer_id or guest_uuid.');
            }
        });
    }

    /**
     * Associated Shopify store.
     *
     * @return BelongsTo<ShopifyStore, $this>
     */
    public function shopifyStore(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shop_id');
    }

    /**
     * Scope for a specific shop.
     *
     * @param  Builder<WishlistItem>  $query
     */
    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for a customer identity.
     *
     * @param  Builder<WishlistItem>  $query
     */
    public function scopeForCustomer(Builder $query, string $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for a guest identity.
     *
     * @param  Builder<WishlistItem>  $query
     */
    public function scopeForGuest(Builder $query, string $guestUuid): Builder
    {
        return $query->where('guest_uuid', $guestUuid);
    }

    /**
     * Scope for either customer or guest identity.
     *
     * @param  Builder<WishlistItem>  $query
     */
    public function scopeForIdentity(Builder $query, ?string $customerId, ?string $guestUuid): Builder
    {
        if ($customerId !== null && trim($customerId) !== '') {
            return $query->where('customer_id', $customerId);
        }

        if ($guestUuid !== null && trim($guestUuid) !== '') {
            return $query->where('guest_uuid', $guestUuid);
        }

        // Return empty result set if neither identity is valid
        return $query->whereRaw('1 = 0');
    }

    /**
     * Format item for storefront payload response.
     *
     * @return array<string, mixed>
     */
    public function toStorefrontArray(): array
    {
        return [
            'id' => $this->product_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'handle' => $this->product_handle,
            'title' => $this->product_title,
            'img' => $this->product_image,
            'price' => $this->product_price,
            'url' => $this->product_url,
            'currency' => $this->currency ?? '$',
            'added_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
