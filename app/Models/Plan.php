<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'price',
        'currency',
        'billing_interval',
        'social_link_limit',
        'description',
        'features',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'social_link_limit' => 'integer',
        ];
    }

    /**
     * Subscriptions associated with this plan.
     *
     * @return HasMany<ShopSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class, 'plan_id');
    }

    /**
     * Check if the plan is free.
     */
    public function isFree(): bool
    {
        return $this->slug === 'free' || (float) $this->price <= 0.0;
    }

    /**
     * Check if the plan is Pro/Premium.
     */
    public function isPro(): bool
    {
        return ! $this->isFree();
    }

    /**
     * Get configured Social Link limit (null means unlimited).
     */
    public function getSocialLinkLimit(): ?int
    {
        return $this->social_link_limit;
    }

    /**
     * Format the price string (e.g. "$0" or "$5.00").
     */
    public function formattedPrice(): string
    {
        if ($this->isFree()) {
            return '$0';
        }

        return '$'.number_format((float) $this->price, 2);
    }
}
