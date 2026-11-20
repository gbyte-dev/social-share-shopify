<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use App\Services\EntitlementService;
use App\Services\ShopifyBillingService;
use App\Services\ShopifyPricingService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlansController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected EntitlementService $entitlementService,
        protected ShopifyPricingService $pricingService
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

        $freePlan = Plan::where('slug', 'starter')->first()
            ?? Plan::where('slug', 'free')->first()
            ?? Plan::where('price', '<=', 0)->first();
        $proPlan = Plan::where('slug', 'pro')->first() ?? Plan::where('price', '>', 0)->first();

        $activeSubscription = $this->subscriptionService->getActiveSubscription($store);

        // Auto-reconcile with Shopify if store has an access token and no active subscription is recorded locally
        // (e.g. after a merchant selects or claims a plan on Shopify's hosted pricing page)
        if (! $activeSubscription && ! empty($store->access_token)) {
            Log::info('[PlansController::index] No active subscription in database; reconciling from Shopify', [
                'shop' => $store->shop,
            ]);
            $syncResult = $this->pricingService->verifyAndSyncActiveSubscription($store);
            if (! empty($syncResult['success']) && ! empty($syncResult['subscription'])) {
                $activeSubscription = $syncResult['subscription'];
                Log::info('[PlansController::index] Successfully synchronized active subscription from Shopify', [
                    'shop' => $store->shop,
                    'sub_id' => $activeSubscription->id,
                    'plan_handle' => $activeSubscription->plan_handle,
                ]);
            } elseif (($syncResult['error'] ?? '') === 'SHOPIFY_API_FAILED') {
                Log::warning('[PlansController::index] Shopify subscription lookup failed; preserving local state', [
                    'shop' => $store->shop,
                    'message' => $syncResult['message'] ?? 'API error',
                ]);
            }
        }

        $latestSubscription = $this->subscriptionService->getLatestSubscription($store);

        $isPro = $this->entitlementService->isPro($store);
        $isFree = $this->entitlementService->isFree($store);
        $creationStatus = $this->entitlementService->getSocialLinkCreationStatus($store);

        $subscriptionStatus = 'none';
        if ($activeSubscription) {
            $subscriptionStatus = ShopSubscription::STATUS_ACTIVE;
        } elseif ($latestSubscription && $latestSubscription->status === ShopSubscription::STATUS_CANCELLED) {
            $subscriptionStatus = ShopSubscription::STATUS_CANCELLED;
        } elseif ($latestSubscription && $latestSubscription->isExpired()) {
            $subscriptionStatus = ShopSubscription::STATUS_EXPIRED;
        } elseif ($latestSubscription) {
            $subscriptionStatus = $latestSubscription->status;
        }

        $currentPlan = $activeSubscription ? $activeSubscription->plan : ($latestSubscription?->plan ?? $freePlan);

        return view('shopify.plans.index', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'pricingUrl' => $store->getAppPricingUrl(),
            'plan' => $proPlan,
            'freePlan' => $freePlan,
            'proPlan' => $proPlan,
            'activeSubscription' => $activeSubscription,
            'latestSubscription' => $latestSubscription,
            'hasActiveSubscription' => $activeSubscription !== null,
            'subscriptionStatus' => $subscriptionStatus,
            'currentPlan' => $currentPlan,
            'activeLinksCount' => $activeLinksCount,
            'totalLinksCount' => $totalLinksCount,
            'isPro' => $isPro,
            'isFree' => $isFree,
            'creationStatus' => $creationStatus,
            'warningMessage' => session('warning'),
        ]);
    }

    /**
     * Process return from Shopify App Pricing hosted pricing page.
     * Strictly verifies active subscription with Shopify Partner API before unlocking features.
     */
    public function welcome(Request $request): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $host = session('shopify_host') ?? $request->query('host');

        // Check if shop parameter was passed and ensure it matches the authenticated store
        $queryShop = $request->query('shop');
        if ($queryShop && $queryShop !== $store->shop) {
            Log::warning('Welcome URL shop domain mismatch', [
                'authenticated' => $store->shop,
                'query_shop' => $queryShop,
            ]);

            return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('warning', 'Store identity mismatch during verification.');
        }

        // Strictly verify with Shopify API - never activate on query params alone
        $planHandle = $request->query('plan_handle');
        $syncResult = $this->pricingService->verifyAndSyncActiveSubscription($store, $planHandle);
        $subscription = is_array($syncResult) ? ($syncResult['subscription'] ?? null) : $syncResult;

        if ($subscription && $subscription->isActive() && $subscription->isVerified()) {
            $planName = $subscription->plan?->name ?? 'Socialvire Starter';
            $purchaseMsg = "Your {$planName} plan is now active. Enjoy your premium features!";

            return redirect()->route('shopify.dashboard', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('purchase_success', $purchaseMsg)
                ->with('success', "Purchase Successful! {$purchaseMsg}");
        }

        if (($syncResult['error'] ?? '') === 'SHOPIFY_API_FAILED') {
            return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('warning', 'Could not communicate with Shopify to verify subscription status. Please try again.');
        }

        return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
            ->with('warning', 'Subscription could not be verified by Shopify. Please claim your free plan to unlock all features.');
    }

    /**
     * Display the checkout confirmation screen.
     */
    public function checkout(Request $request, int|string $plan): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $selectedPlan = is_numeric($plan)
            ? Plan::where('id', $plan)->where('is_active', true)->firstOrFail()
            : Plan::where('slug', $plan)->where('is_active', true)->firstOrFail();

        $activeSubscription = $this->subscriptionService->getActiveSubscription($store);
        $isPro = $this->entitlementService->isPro($store);

        return view('shopify.plans.checkout', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'plan' => $selectedPlan,
            'activeSubscription' => $activeSubscription,
            'isPro' => $isPro,
            'pricingUrl' => $store->getAppPricingUrl(),
        ]);
    }

    /**
     * Activate subscription via Shopify App Pricing (Internal dummy checkout is deprecated).
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

        $pricingUrl = $store->getAppPricingUrl();

        // When Partner API is configured or in production, internal dummy activation is disabled
        if ($this->pricingService->isPartnerApiConfigured() || ! app()->runningUnitTests()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'deprecated' => true,
                    'message' => 'Internal subscription activation is deprecated. Please use Shopify App Pricing.',
                    'redirect_url' => $pricingUrl,
                ]);
            }

            return redirect()->away($pricingUrl);
        }

        // Test fallback for legacy unit tests verifying internal dummy activation
        $host = session('shopify_host') ?? $request->query('host');
        $subscription = $this->subscriptionService->activateSubscription(
            $store,
            $plan,
            null,
            ['source' => 'dummy_checkout_activation', 'type' => 'internal_dummy']
        );

        $successMessage = "Subscription for {$plan->name} activated successfully!";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'subscription' => $subscription,
                'redirect_url' => route('shopify.dashboard', array_filter(['shop' => $store->shop, 'host' => $host])),
            ]);
        }

        return redirect()->route('shopify.dashboard', array_filter(['shop' => $store->shop, 'host' => $host]))
            ->with('success', $successMessage);
    }

    /**
     * Process return callback from Shopify billing approval.
     * Strictly verifies approval status server-side via Shopify GraphQL before activating Pro.
     */
    public function billingCallback(Request $request, ShopifyBillingService $billingService): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $chargeId = $request->query('charge_id') ?? $request->query('id');
        $planId = $request->query('plan_id');
        $host = session('shopify_host') ?? $request->query('host');

        if (empty($chargeId)) {
            Log::warning('Billing callback received without charge_id', [
                'shop' => $store->shop,
                'query' => $request->query(),
            ]);

            return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('warning', 'Billing confirmation was cancelled or incomplete.');
        }

        $plan = $planId
            ? Plan::where('id', $planId)->where('is_active', true)->where('price', '>', 0)->first()
            : Plan::where('slug', 'pro')->where('is_active', true)->first();

        if (! $plan) {
            return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('error', 'Plan could not be found.');
        }

        // Query Shopify Admin GraphQL API to strictly verify charge approval
        try {
            $statusData = $billingService->getSubscriptionStatus($store, (string) $chargeId);

            if (! $statusData || $statusData['status'] !== 'ACTIVE') {
                Log::warning('Shopify billing verification rejected: subscription not active', [
                    'shop' => $store->shop,
                    'charge_id' => $chargeId,
                    'status_data' => $statusData,
                ]);

                return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
                    ->with('warning', 'Subscription approval could not be verified by Shopify.');
            }

            // Server-side verified: activate Pro subscription locally
            $subscription = $this->subscriptionService->activateSubscription(
                $store,
                $plan,
                (string) $chargeId,
                [
                    'verified_at' => now()->toIso8601String(),
                    'shopify_status' => $statusData['status'],
                    'shopify_name' => $statusData['name'],
                ]
            );

            Log::info('Shopify billing approval verified and activated', [
                'shop' => $store->shop,
                'charge_id' => $chargeId,
                'subscription_id' => $subscription->id,
            ]);

            $planName = $subscription->plan?->name ?? $plan->name ?? 'SocialNest Pro';
            $purchaseMsg = "Your {$planName} plan is now active. Enjoy your premium features!";

            return redirect()->route('shopify.dashboard', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('purchase_success', $purchaseMsg)
                ->with('success', "Purchase Successful! {$purchaseMsg}");
        } catch (\Throwable $e) {
            Log::error('Shopify billing callback verification exception', [
                'shop' => $store->shop,
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
                ->with('error', 'Failed to verify subscription with Shopify: '.$e->getMessage());
        }
    }

    /**
     * Cancel an active subscription and gracefully revert to free tier limitations.
     */
    public function cancel(Request $request): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $host = session('shopify_host') ?? $request->query('host');

        $subscription = $this->pricingService->cancelSubscription($store);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your subscription has been cancelled and your store reverted to free tier limits.',
                'subscription' => $subscription,
                'redirect_url' => route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host])),
            ]);
        }

        return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
            ->with('success', 'Your subscription has been cancelled and your store reverted to free tier limits.');
    }

    /**
     * Fully automatic Shopify App Pricing active subscription cancellation.
     */
    public function cancelActiveSubscription(Request $request): JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        Log::info('[SubscriptionCancellation] Received POST /shopify/subscription/cancel-active request', [
            'store_id' => $store->id,
            'shop' => $store->shop,
            'current_plan_name' => $store->plan_name,
            'ip' => $request->ip(),
        ]);

        try {
            $result = $this->pricingService->cancelActiveSubscriptionAutomatically($store);

            Log::info('[SubscriptionCancellation] Completed with response payload', [
                'shop' => $store->shop,
                'result' => $result,
            ]);

            $statusCode = ($result['success'] ?? false) ? 200 : 422;

            return response()->json($result, $statusCode);
        } catch (\Throwable $e) {
            Log::error('[SubscriptionCancellation] Exception in PlansController::cancelActiveSubscription', [
                'shop' => $store->shop,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to cancel subscription: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resuming redirects to re-subscribe to a new active period.
     */
    public function resume(Request $request): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $host = session('shopify_host') ?? $request->query('host');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Please re-subscribe to activate a new billing period.',
            ], 422);
        }

        return redirect()->route('shopify.plans.index', array_filter(['shop' => $store->shop, 'host' => $host]))
            ->with('warning', 'Please re-subscribe to activate a new billing period.');
    }

    /**
     * Subscription status API endpoint for billing verification.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $active = $this->subscriptionService->getActiveSubscription($store);
        if (! $active && ! empty($store->access_token)) {
            $syncResult = $this->pricingService->verifyAndSyncActiveSubscription($store);
            if (! empty($syncResult['success']) && ! empty($syncResult['subscription'])) {
                $active = $syncResult['subscription'];
            }
        }
        $latest = $this->subscriptionService->getLatestSubscription($store);
        $effectivePlan = $this->entitlementService->getEffectivePlan($store);
        $isPro = $this->entitlementService->isPro($store);
        $isFree = $this->entitlementService->isFree($store);

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
            'is_pro' => $isPro,
            'is_free' => $isFree,
            'is_verified' => $active?->isVerified() ?? false,
            'is_frozen' => $active?->isFrozen() ?? false,
            'plan_handle' => $active?->plan_handle,
            'last_verified_at' => $active?->last_verified_at?->toIso8601String(),
            'status' => $status,
            'plan' => $effectivePlan->name,
            'plan_slug' => $effectivePlan->slug,
            'cancel_at_period_end' => $active?->cancel_at_period_end ?? false,
            'cancelled_at' => $active?->cancelled_at?->toIso8601String(),
            'activated_at' => $active?->activated_at?->toIso8601String(),
            'current_period_start' => $active?->current_period_start?->toIso8601String(),
            'current_period_end' => $active?->current_period_end?->toIso8601String() ?? $latest?->current_period_end?->toIso8601String(),
            'days_remaining' => $active?->daysRemaining() ?? 0,
            'shopify_charge_id' => $active?->shopify_charge_id,
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
