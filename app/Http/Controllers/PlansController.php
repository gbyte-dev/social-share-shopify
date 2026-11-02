<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display the Plans & Billing overview.
     */
    public function index(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $activeLinksCount = $store->socialLinks()->where('is_active', true)->count();
        $totalLinksCount = $store->socialLinks()->count();

        // Exactly ONE plan exposed: SocialNest Pro ($5.00)
        $proPlan = Plan::where('is_active', true)->where('price', '>', 0)->firstOrFail();
        $activeSubscription = $this->subscriptionService->getActiveSubscription($store);
        $latestSubscription = $this->subscriptionService->getLatestSubscription($store);

        $hasActiveSubscription = $activeSubscription !== null;
        $subscriptionStatus = 'none';

        if ($hasActiveSubscription) {
            $subscriptionStatus = ShopSubscription::STATUS_ACTIVE;
        } elseif ($latestSubscription && $latestSubscription->status === ShopSubscription::STATUS_CANCELLED) {
            $subscriptionStatus = ShopSubscription::STATUS_CANCELLED;
        } elseif ($latestSubscription && $latestSubscription->isExpired()) {
            $subscriptionStatus = ShopSubscription::STATUS_EXPIRED;
        } elseif ($latestSubscription) {
            $subscriptionStatus = $latestSubscription->status;
        }

        $currentPlan = $activeSubscription ? $activeSubscription->plan : ($latestSubscription?->plan ?? $proPlan);

        return view('shopify.plans.index', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'plan' => $proPlan,
            'activeSubscription' => $activeSubscription,
            'latestSubscription' => $latestSubscription,
            'hasActiveSubscription' => $hasActiveSubscription,
            'subscriptionStatus' => $subscriptionStatus,
            'currentPlan' => $currentPlan,
            'activeLinksCount' => $activeLinksCount,
            'totalLinksCount' => $totalLinksCount,
            'warningMessage' => session('warning') ?? (! $hasActiveSubscription ? 'Paid plan is required to use features on this app' : null),
        ]);
    }

    /**
     * Display the dummy checkout confirmation screen.
     */
    public function checkout(Request $request, int|string $plan): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        // Only allow active, paid plans (SocialNest Pro)
        $selectedPlan = is_numeric($plan)
            ? Plan::where('id', $plan)->where('is_active', true)->where('price', '>', 0)->firstOrFail()
            : Plan::where('slug', $plan)->where('is_active', true)->where('price', '>', 0)->firstOrFail();

        $activeSubscription = $this->subscriptionService->getActiveSubscription($store);

        return view('shopify.plans.checkout', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'plan' => $selectedPlan,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    /**
     * Confirm and activate the dummy 30-day subscription.
     */
    public function subscribe(Request $request): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::where('id', $validated['plan_id'])
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->firstOrFail();

        $subscription = $this->subscriptionService->activateSubscription($store, $plan);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Subscription for {$plan->name} activated successfully! Active for 30 days.",
                'subscription' => $subscription,
                'redirect_url' => route('shopify.dashboard', [
                    'shop' => $store->shop,
                    'host' => session('shopify_host') ?? $request->query('host'),
                ]),
            ]);
        }

        return redirect()->route('shopify.dashboard', [
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
        ])->with('success', "Subscription for {$plan->name} activated successfully! Active for 30 days.");
    }

    /**
     * Cancel an active subscription immediately.
     */
    public function cancel(Request $request): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $subscription = $this->subscriptionService->cancelSubscription($store);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your subscription has been cancelled and access has been revoked immediately.',
                'subscription' => $subscription,
            ]);
        }

        return redirect()->route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
        ])->with('success', 'Your subscription has been cancelled and access has been revoked immediately.');
    }

    /**
     * Resuming is not supported under instant cancellation; redirect to re-subscribe.
     */
    public function resume(Request $request): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriptions are cancelled immediately. Please re-subscribe to restore access.',
            ], 422);
        }

        return redirect()->route('shopify.plans.index', [
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
        ])->with('warning', 'Subscriptions are cancelled immediately. Please re-subscribe to activate a new 30-day period.');
    }

    /**
     * Subscription status API endpoint for billing verification.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $active = $this->subscriptionService->getActiveSubscription($store);
        $latest = $this->subscriptionService->getLatestSubscription($store);

        $status = 'none';
        if ($active) {
            $status = ShopSubscription::STATUS_ACTIVE;
        } elseif ($latest && $latest->status === ShopSubscription::STATUS_CANCELLED) {
            $status = ShopSubscription::STATUS_CANCELLED;
        } elseif ($latest && $latest->isExpired()) {
            $status = ShopSubscription::STATUS_EXPIRED;
        } elseif ($latest) {
            $status = $latest->status;
        }

        return response()->json([
            'shop' => $store->shop,
            'is_active' => $active !== null,
            'status' => $status,
            'plan' => $active?->plan->name ?? $latest?->plan->name ?? $store->plan_name,
            'cancel_at_period_end' => $active?->cancel_at_period_end ?? false,
            'cancelled_at' => $active?->cancelled_at?->toIso8601String(),
            'activated_at' => $active?->activated_at?->toIso8601String(),
            'current_period_start' => $active?->current_period_start?->toIso8601String(),
            'current_period_end' => $active?->current_period_end?->toIso8601String() ?? $latest?->current_period_end?->toIso8601String(),
            'days_remaining' => $active?->daysRemaining() ?? 0,
        ]);
    }

    /**
     * Resolve and strictly verify the authenticated Shopify store.
     */
    protected function resolveStore(Request $request): ShopifyStore
    {
        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store || ! $store->isInstalled()) {
            $sessionShop = session('shopify_shop');
            $sessionStoreId = session('shopify_store_id');

            if ($sessionShop && $sessionStoreId) {
                $store = ShopifyStore::where('shop', $sessionShop)
                    ->where('id', $sessionStoreId)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();
            }

            if (! $store || ! $store->isInstalled()) {
                throw new HttpResponseException(
                    redirect()->route('shopify.install')->with('error', 'Authentication required.')
                );
            }
        }

        return $store;
    }
}
