<?php

namespace App\Models;

use App\Services\EntitlementService;
use Database\Factories\ShopifyStoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShopifyStore extends Model
{
    /** @use HasFactory<ShopifyStoreFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shopify_stores';

    /**
     * Flag controlling whether newly created stores in testing environment automatically receive an active subscription.
     */
    public static bool $autoSubscribeInTests = true;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (ShopifyStore $store): void {
            if (app()->environment('testing') && static::$autoSubscribeInTests) {
                $plan = Plan::where('slug', 'pro')->first() ?? Plan::firstOrCreate(
                    ['slug' => 'pro'],
                    [
                        'name' => 'SocialNest Pro',
                        'price' => 5.00,
                        'currency' => 'USD',
                        'billing_interval' => '30_days',
                        'features' => ['Complete features'],
                        'is_active' => true,
                    ]
                );

                ShopSubscription::create([
                    'shop_id' => $store->id,
                    'plan_id' => $plan->id,
                    'status' => ShopSubscription::STATUS_ACTIVE,
                    'cancel_at_period_end' => false,
                    'activated_at' => now(),
                    'current_period_start' => now(),
                    'current_period_end' => now()->addDays(30),
                ]);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shop',
        'shopify_shop_gid',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'scope',
        'plan_name',
        'is_active',
        'reauthorization_required',
        'installed_at',
        'uninstalled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'reauthorization_required' => 'boolean',
            'installed_at' => 'datetime',
            'uninstalled_at' => 'datetime',
        ];
    }

    /**
     * Check if this store currently requires reauthorization via Shopify OAuth.
     */
    public function requiresReauthorization(): bool
    {
        if ($this->reauthorization_required) {
            return true;
        }

        // If access token is expired and refresh token is missing or expired, reauthorization is strictly required
        if ($this->access_token_expires_at && $this->access_token_expires_at->isPast()) {
            if (empty($this->refresh_token) || ($this->refresh_token_expires_at && $this->refresh_token_expires_at->isPast())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the store is currently active and installed.
     */
    public function isInstalled(): bool
    {
        return $this->is_active
            && ! empty($this->access_token)
            && ! $this->requiresReauthorization()
            && $this->installed_at !== null
            && $this->uninstalled_at === null;
    }

    /**
     * Mark the store as installed.
     */
    public function markInstalled(string $accessToken, ?string $scope = null): void
    {
        $this->update([
            'access_token' => $accessToken,
            'scope' => $scope,
            'is_active' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ]);
    }

    /**
     * Mark the store as uninstalled.
     */
    public function markUninstalled(): void
    {
        $this->update([
            'is_active' => false,
            'uninstalled_at' => now(),
        ]);
    }

    /**
     * Social media links configured by this merchant.
     *
     * @return HasMany<SocialLink, $this>
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(SocialLink::class, 'shopify_store_id')->orderBy('sort_order', 'asc');
    }

    /**
     * Storefront widget customization settings.
     *
     * @return HasOne<StorefrontSetting, $this>
     */
    public function storefrontSettings(): HasOne
    {
        return $this->hasOne(StorefrontSetting::class, 'shopify_store_id');
    }

    /**
     * Storefront click tracking events.
     *
     * @return HasMany<SocialLinkClick, $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(SocialLinkClick::class, 'shopify_store_id');
    }

    /**
     * Storefront customer and guest wishlist items.
     *
     * @return HasMany<WishlistItem, $this>
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'shop_id');
    }

    /**
     * Subscriptions for this store.
     *
     * @return HasMany<ShopSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class, 'shop_id')->orderBy('id', 'desc');
    }

    /**
     * Currently active subscription for this store.
     *
     * @return HasOne<ShopSubscription, $this>
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(ShopSubscription::class, 'shop_id')
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('current_period_start', '<=', now())
            ->where(function ($q): void {
                $q->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            })
            ->latestOfMany();
    }

    /**
     * Determine if store has active Pro subscription.
     */
    public function isPro(): bool
    {
        return app(EntitlementService::class)->isPro($this);
    }

    /**
     * Determine if store is on Free tier.
     */
    public function isFree(): bool
    {
        return app(EntitlementService::class)->isFree($this);
    }

    /**
     * Check if store can create additional social link records.
     */
    public function canCreateSocialLink(): bool
    {
        return app(EntitlementService::class)->canCreateSocialLink($this);
    }

    /**
     * Get the store handle from the myshopify domain.
     */
    public function getShopHandle(): string
    {
        return preg_replace('/\.myshopify\.com$/i', '', strtolower(trim($this->shop)));
    }

    /**
     * Generate the Shopify hosted pricing URL for this store.
     */
    public function getAppPricingUrl(?string $appHandle = null): string
    {
        $appHandle = $appHandle ?: config('shopify.app_handle', 'socialvire');
        $handle = $this->getShopHandle();

        return "https://admin.shopify.com/store/{$handle}/charges/{$appHandle}/pricing_plans";
    }

    /**
     * Get or create storefront settings for this store.
     */
    public function getStorefrontSettings(): StorefrontSetting
    {
        return $this->storefrontSettings()->firstOrCreate([], [
            'is_enabled' => true,
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
            'whatsapp_message' => 'Check out this product on '.$this->shop,
            'product_share_enabled' => true,
            'product_share_button_text' => 'Share Product',
            'product_share_button_style' => 'outline',
            'product_share_button_background_color' => 'rgba(17, 24, 39, 1)',
        ]);
    }
}
