<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Collection;

class EntitlementService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Resolve the effective active subscription for a store.
     */
    public function getEffectiveSubscription(ShopifyStore $store): ?ShopSubscription
    {
        return $this->subscriptionService->getActiveSubscription($store);
    }

    /**
     * Resolve the effective plan for a store (defaults to active Free plan if none active).
     */
    public function getEffectivePlan(ShopifyStore $store): Plan
    {
        $subscription = $this->getEffectiveSubscription($store);

        if ($subscription && $subscription->plan && $subscription->plan->is_active) {
            return $subscription->plan;
        }

        return Plan::where('slug', 'free')->where('is_active', true)->first()
            ?? Plan::where('price', '<=', 0)->where('is_active', true)->firstOrFail();
    }

    /**
     * Determine if store has verified full entitlements (either verified Shopify App Pricing or legacy Pro).
     */
    public function hasFullEntitlements(ShopifyStore $store): bool
    {
        $subscription = $this->getEffectiveSubscription($store);

        if (! $subscription || ! $subscription->isActive() || $subscription->isFrozen()) {
            return false;
        }

        // Verified active Shopify App Pricing subscription
        if ($subscription->isVerified()) {
            return true;
        }

        // Legacy active Pro plan (for backwards compatibility)
        return $subscription->plan?->isPro() === true;
    }

    /**
     * Determine if store has verified Pro (Premium / Full) entitlements.
     */
    public function isPro(ShopifyStore $store): bool
    {
        return $this->hasFullEntitlements($store);
    }

    /**
     * Determine if store is on Free tier.
     */
    public function isFree(ShopifyStore $store): bool
    {
        return ! $this->hasFullEntitlements($store);
    }

    /**
     * Check if the store is eligible to create a new Social Link.
     *
     * Rules:
     * - Unverified / Free tier: maximum 2 existing non-deleted records.
     * - Verified Shopify App Pricing / Pro: unlimited (or configured limit).
     */
    public function canCreateSocialLink(ShopifyStore $store): bool
    {
        $status = $this->getSocialLinkCreationStatus($store);

        return $status['can_create'];
    }

    /**
     * Get detailed status and limit information for Social Links creation.
     *
     * @return array{can_create: bool, current_count: int, limit: ?int, is_pro: bool, message: ?string}
     */
    public function getSocialLinkCreationStatus(ShopifyStore $store): array
    {
        $currentCount = $store->socialLinks()->count();
        $hasFull = $this->hasFullEntitlements($store);
        $subscription = $this->getEffectiveSubscription($store);

        if ($hasFull) {
            $plan = $this->getEffectivePlan($store);
            $planLimit = $plan->getSocialLinkLimit();

            // Verified Free plan gives unlimited links (null)
            if ($subscription && $subscription->isVerified() && $plan->isFree()) {
                $limit = null;
            } elseif ($planLimit !== null) {
                // Tiered plan with explicit limit (e.g. Starter tier with 10 links)
                $limit = $planLimit;
            } else {
                // Pro tier with no limit is unlimited
                $limit = null;
            }
        } else {
            $limit = 2;
        }

        $canCreate = $limit === null || $currentCount < $limit;

        $message = null;
        if (! $canCreate) {
            $message = $hasFull
                ? "You have reached your plan limit of {$limit} social links."
                : 'Free plan is limited to 2 social links. Upgrade to SocialNest Pro for unlimited links.';
        }

        return [
            'can_create' => $canCreate,
            'current_count' => $currentCount,
            'limit' => $limit,
            'is_pro' => $hasFull,
            'message' => $message,
        ];
    }

    /**
     * Get storefront eligible active social links for the store.
     *
     * Rules:
     * - Active links only (`is_active = true`).
     * - Deterministic order: `sort_order ASC, id ASC`.
     * - Unverified / Free tier: maximum 2 links returned (never deleting the rest).
     * - Verified / Pro tier: all active links returned (or bounded by configured limit if any).
     *
     * @return Collection<int, SocialLink>
     */
    public function getStorefrontSocialLinks(ShopifyStore $store): Collection
    {
        $query = $store->socialLinks()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');

        if (! $this->hasFullEntitlements($store)) {
            return $query->take(2)->get();
        }

        $subscription = $this->getEffectiveSubscription($store);
        $plan = $this->getEffectivePlan($store);

        // Verified Free plan displays all active links without limit
        if ($subscription && $subscription->isVerified() && $plan->isFree()) {
            return $query->get();
        }

        $limit = $plan->getSocialLinkLimit();

        if ($limit !== null && $limit > 0) {
            $query->take($limit);
        }

        return $query->get();
    }

    /**
     * Determine if store can access Storefront Settings page and APIs.
     * Completely Premium-only.
     */
    public function canAccessStorefrontSettings(ShopifyStore $store): bool
    {
        return $this->isPro($store);
    }

    /**
     * Determine if store can access Wishlist backend setup, status, and storefront operations.
     * Completely Premium-only.
     */
    public function canAccessWishlist(ShopifyStore $store): bool
    {
        return $this->isPro($store);
    }

    /**
     * Determine if store can access general Premium features (CTA, product share, advanced styling).
     */
    public function canAccessPremiumFeatures(ShopifyStore $store): bool
    {
        return $this->isPro($store);
    }
}
