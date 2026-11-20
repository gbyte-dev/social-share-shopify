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

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_PENDING = 'pending';

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
        'shopify_charge_id',
        'shopify_app_gid',
        'plan_handle',
        'status',
        'cancel_at_period_end',
        'cancelled_at',
        'activated_at',
        'last_verified_at',
        'billing_interval',
        'current_period_start',
        'current_period_end',
        'metadata',
        'response_snapshot',
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
            'last_verified_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'metadata' => 'array',
            'response_snapshot' => 'array',
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
     * Determine if this subscription has been verified through Shopify Partner API.
     */
    public function isVerified(): bool
    {
        return $this->last_verified_at !== null || ! empty($this->plan_handle);
    }

    /**
     * Determine if this subscription is in a frozen state.
     */
    public function isFrozen(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    /**
     * Determine if this subscription is permanent free tier.
     */
    public function isPermanentFree(): bool
    {
        $plan = $this->plan;

        return $plan !== null && $plan->isFree() && $this->current_period_end === null;
    }

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $plan = $this->plan;

        if (! $plan || ! $plan->is_active) {
            return false;
        }

        // Free plan semantics: valid permanently if current_period_end is null or in the future
        if ($plan->isFree()) {
            if ($this->current_period_end === null) {
                return true;
            }

            return $this->current_period_end->gt(now());
        }

        // Paid / Pro plan semantics: must have valid non-expired billing period
        if (! $this->current_period_start || ! $this->current_period_end) {
            return false;
        }

        $now = now();

        if ($this->current_period_start->gt($now) || $this->current_period_end->lte($now)) {
            return false;
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
        return $this->isActive() && $this->plan?->isPro();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->isPermanentFree()) {
            return false;
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
        if (! $this->isActive()) {
            return 0;
        }

        if ($this->isPermanentFree() || $this->current_period_end === null) {
            return -1;
        }

        $now = now();
        $diffSeconds = $now->diffInSeconds($this->current_period_end, false);

        if ($diffSeconds <= 0) {
            return 0;
        }

        return (int) ceil($diffSeconds / 86400);
    }
}
