<?php

namespace App\Http\Middleware;

use App\Models\ShopifyStore;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Route names that are explicitly exempt from requiring an active subscription.
     *
     * @var list<string>
     */
    protected array $exemptRouteNames = [
        'home',
        'shopify.install',
        'shopify.callback',
        'shopify.callback.alias',
        'shopify.logout',
        'shopify.plans.index',
        'shopify.plans.checkout',
        'shopify.plans.subscribe',
        'shopify.plans.cancel',
        'shopify.plans.resume',
        'shopify.plans.status',
        'shopify.webhooks',
        'api.storefront.config',
        'api.storefront.track-click',
    ];

    /**
     * URI patterns that are explicitly exempt.
     *
     * @var list<string>
     */
    protected array $exemptPatterns = [
        '/',
        'shopify/install*',
        'shopify/callback*',
        'callback*',
        'shopify/logout*',
        'shopify/plans*',
        'shopify/webhooks*',
        'api/storefront/*',
        'up',
    ];

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store) {
            $sessionStoreId = session('shopify_store_id');
            if ($sessionStoreId) {
                $store = ShopifyStore::find($sessionStoreId);
            }
        }

        // If no store can be resolved, let shopify.auth or install handle authentication
        if (! $store) {
            return $next($request);
        }

        if (! $this->subscriptionService->isActive($store)) {
            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'error' => 'SUBSCRIPTION_REQUIRED',
                    'code' => 'SUBSCRIPTION_REQUIRED',
                    'message' => 'Paid plan is required to use features on this app',
                    'upgrade_url' => route('shopify.plans.index', [
                        'shop' => $store->shop,
                        'host' => session('shopify_host') ?? $request->query('host'),
                    ]),
                ], 403);
            }

            return redirect()->route('shopify.plans.index', [
                'shop' => $store->shop,
                'host' => session('shopify_host') ?? $request->query('host'),
            ])->with('warning', 'Paid plan is required to use features on this app');
        }

        return $next($request);
    }

    /**
     * Determine if the request is exempt from subscription check.
     */
    protected function isExempt(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $this->exemptRouteNames, true)) {
            return true;
        }

        foreach ($this->exemptPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
