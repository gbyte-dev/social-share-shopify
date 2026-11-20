<?php

namespace App\Http\Middleware;

use App\Models\ShopifyStore;
use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProSubscription
{
    public function __construct(
        protected EntitlementService $entitlementService
    ) {}

    /**
     * Handle an incoming request requiring an active Pro (Premium) subscription.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store) {
            $sessionStoreId = session('shopify_store_id');
            if ($sessionStoreId) {
                $store = ShopifyStore::find($sessionStoreId);
            }
        }

        if (! $store) {
            $shop = $request->query('shop') ?? $request->header('X-Shopify-Shop-Domain');
            if ($shop) {
                $store = ShopifyStore::where('shop', $shop)->first();
            }
        }

        if (! $store) {
            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Store context could not be resolved.',
                ], 401);
            }

            return redirect()->route('shopify.install');
        }

        if (! $this->entitlementService->isPro($store)) {
            // Determine if request is a true API/JSON call rather than a client-side page navigation
            $isApiRequest = $request->expectsJson()
                || $request->is('api/*')
                || ($request->ajax() && ! $request->hasHeader('X-Shopify-Navigation'));

            if ($isApiRequest) {
                return response()->json([
                    'error' => 'UPGRADE_REQUIRED',
                    'code' => 'UPGRADE_REQUIRED',
                    'message' => 'This feature requires a SocialNest Pro subscription.',
                    'upgrade_url' => route('shopify.plans.index', [
                        'shop' => $store->shop,
                        'host' => session('shopify_host') ?? $request->query('host'),
                    ]),
                ], 403);
            }

            return redirect()->route('shopify.plans.index', [
                'shop' => $store->shop,
                'host' => session('shopify_host') ?? $request->query('host'),
            ])->with('warning', 'This feature requires a SocialNest Pro subscription.');
        }

        return $next($request);
    }
}
