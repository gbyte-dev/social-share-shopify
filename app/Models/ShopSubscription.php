<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSubscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shop_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shop_id',
        'plan_id',
        'status',
        'cancel_at_period_end',
        'cancelled_at',
        'activated_at',
        'current_period_start',
        'current_period_end',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancel_at_period_end' => 'boolean',
            'cancelled_at' => 'datetime',
            'activated_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
        ];
    }

    /**
     * The Shopify store that owns this subscription.
     *
     * @return BelongsTo<ShopifyStore, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shop_id');
    }

    /**
     * The Plan associated with this subscription.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (! $this->current_period_start || ! $this->current_period_end) {
            return false;
        }

        $now = now();

        if ($this->current_period_start->gt($now) || $this->current_period_end->lte($now)) {
            return false;
        }

        // Only active, paid plans (such as SocialNest Pro) grant access
        if ($this->relationLoaded('plan') && $this->plan) {
            return $this->plan->is_active && ! $this->plan->isFree();
        }

        return true;
    }

    /**
     * Check if cancellation was performed.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED || $this->cancelled_at !== null;
    }

    /**
     * Check if the subscription can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->isActive();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->current_period_end && $this->current_period_end->lte(now())) {
            return true;
        }

        return false;
    }

    /**
     * Calculate remaining days in the active billing period.
     */
    public function daysRemaining(): int
    {
        if (! $this->isActive() || ! $this->current_period_end) {
            return 0;
        }

        $now = now();
        $diffSeconds = $now->diffInSeconds($this->current_period_end, false);

        if ($diffSeconds <= 0) {
            return 0;
        }

        return (int) ceil($diffSeconds / 86400);
    }
}
