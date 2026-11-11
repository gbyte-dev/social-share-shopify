<?php

namespace App\Http\Middleware;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopifyShopInstalled
{
    public function __construct(
        protected ShopifyService $shopifyService
    ) {}

    /**
     * Handle an incoming request for protected Shopify merchant routes.
     *
     * State Machine:
     * - installed_authenticated: Active DB record matches session OR valid launch HMAC matches active DB record.
     * - not_installed: Store not found in DB or inactive/uninstalled -> redirect to install/OAuth.
     * - authentication_pending: Store needs authentication/session binding -> redirect to install/OAuth without loop.
     * - authentication_failed: Invalid HMAC signature or loop detected -> render error view with action.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionShop = session('shopify_shop');
        $sessionStoreId = session('shopify_store_id');
        $queryShop = $request->query('shop') ?? $request->input('shop');
        $routeName = $request->route()?->getName() ?? 'unknown';

        // Case 1: A shop query parameter is explicitly provided in the request
        if (! empty($queryShop)) {
            $canonicalQueryShop = $this->shopifyService->normalizeShopDomain($queryShop);

            if (! $canonicalQueryShop) {
                Log::warning('Shopify auth state: authentication_failed - Invalid store domain format', [
                    'route' => $routeName,
                    'raw_shop' => $queryShop,
                ]);

                return redirect()->route('shopify.install')
                    ->with('error', 'Please provide a valid myshopify.com store domain.');
            }

            // Subcase 1A: The query shop matches the existing authenticated session
            if ($sessionShop === $canonicalQueryShop && $sessionStoreId) {
                /** @var ShopifyStore|null $store */
                $store = ShopifyStore::where('shop', $canonicalQueryShop)
                    ->where('id', $sessionStoreId)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();

                if ($store && $store->isInstalled()) {
                    $this->clearRedirectLoopCounter($canonicalQueryShop, $request);

                    Log::debug('Shopify auth state: installed_authenticated via session', [
                        'route' => $routeName,
                        'shop' => $canonicalQueryShop,
                        'store_id' => $store->id,
                    ]);

                    $request->attributes->set('shopify_store', $store);

                    return $next($request);
                }
            }

            // Subcase 1B: The request comes with Shopify launch HMAC signature
            if ($request->has('hmac')) {
                $isHmacValid = $this->shopifyService->verifyHmac($request->query());

                if (! $isHmacValid) {
                    Log::warning('Shopify auth state: authentication_failed - Invalid HMAC signature', [
                        'route' => $routeName,
                        'shop' => $canonicalQueryShop,
                    ]);

                    return response()->view('shopify.error', [
                        'title' => 'Signature Verification Failed',
                        'message' => 'Invalid launch signature. The request could not be authenticated by Shopify.',
                        'shop' => $canonicalQueryShop,
                    ], 403);
                }

                // HMAC is valid, check if this store is installed in database
                /** @var ShopifyStore|null $store */
                $store = ShopifyStore::where('shop', $canonicalQueryShop)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();

                if ($store && $store->isInstalled()) {
                    session([
                        'shopify_shop' => $store->shop,
                        'shopify_store_id' => $store->id,
                        'shopify_host' => $request->query('host'),
                    ]);

                    $this->clearRedirectLoopCounter($canonicalQueryShop, $request);

                    Log::info('Shopify auth state: installed_authenticated via valid launch HMAC', [
                        'route' => $routeName,
                        'shop' => $canonicalQueryShop,
                        'store_id' => $store->id,
                    ]);

                    $request->attributes->set('shopify_store', $store);

                    return $next($request);
                }

                // Valid HMAC but not installed or token missing -> start install flow
                Log::info('Shopify auth state: not_installed - Valid HMAC but store not in DB', [
                    'route' => $routeName,
                    'shop' => $canonicalQueryShop,
                ]);

                return $this->redirectToInstall($request, $canonicalQueryShop, 'not_installed');
            }

            // Subcase 1C: No HMAC provided.
            // If there's an existing valid session for a DIFFERENT store,
            // we must NOT allow cross-store hijack or switch without re-authentication!
            if ($sessionShop && $sessionShop !== $canonicalQueryShop) {
                /** @var ShopifyStore|null $existingSessionStore */
                $existingSessionStore = ShopifyStore::where('shop', $sessionShop)
                    ->where('id', $sessionStoreId)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();

                if ($existingSessionStore && $existingSessionStore->isInstalled()) {
                    $this->clearRedirectLoopCounter($sessionShop, $request);

                    Log::info('Shopify auth state: installed_authenticated - Retaining existing session store', [
                        'route' => $routeName,
                        'session_shop' => $sessionShop,
                        'ignored_query_shop' => $canonicalQueryShop,
                    ]);

                    $request->attributes->set('shopify_store', $existingSessionStore);

                    return $next($request);
                }
            }

            // Subcase 1D: No HMAC and no matching session for this store -> redirect to install
            Log::info('Shopify auth state: authentication_pending - Unauthenticated store query', [
                'route' => $routeName,
                'shop' => $canonicalQueryShop,
            ]);

            return $this->redirectToInstall($request, $canonicalQueryShop, 'authentication_pending');
        }

        // Case 2: No shop query parameter provided (internal tab navigation)
        if ($sessionShop) {
            /** @var ShopifyStore|null $store */
            $storeQuery = ShopifyStore::where('shop', $sessionShop)
                ->where('is_active', true)
                ->whereNotNull('access_token');

            if ($sessionStoreId) {
                $storeQuery->where('id', $sessionStoreId);
            }

            $store = $storeQuery->first();

            if ($store && $store->isInstalled()) {
                $this->clearRedirectLoopCounter($sessionShop, $request);

                Log::debug('Shopify auth state: installed_authenticated via active session', [
                    'route' => $routeName,
                    'shop' => $sessionShop,
                ]);

                $request->attributes->set('shopify_store', $store);

                return $next($request);
            }

            // Session has an uninstalled or inactive store -> clear stale session and redirect to install
            Log::info('Shopify auth state: not_installed - Stale or uninstalled store in session', [
                'route' => $routeName,
                'shop' => $sessionShop,
            ]);

            session()->forget(['shopify_shop', 'shopify_store_id']);

            return $this->redirectToInstall($request, $sessionShop, 'not_installed');
        }

        // Neither valid session nor verified shop query -> redirect to install
        Log::info('Shopify auth state: authentication_pending - Direct unauthenticated access', [
            'route' => $routeName,
        ]);

        return $this->redirectToInstall($request, null, 'authentication_pending');
    }

    /**
     * Redirect to the installation entry point with loop protection.
     */
    protected function redirectToInstall(Request $request, ?string $canonicalShop, string $authState): Response
    {
        if ($this->isRedirectLooping($request, $canonicalShop)) {
            return $this->handleRedirectLoop($request, $canonicalShop);
        }

        $host = $request->query('host');

        $installUrl = route('shopify.install', array_filter([
            'shop' => $canonicalShop,
            'host' => $host,
        ]));

        Log::info('Redirecting to installation entry point', [
            'shop' => $canonicalShop,
            'auth_state' => $authState,
            'destination' => $installUrl,
        ]);

        return redirect()->to($installUrl)
            ->with('warning', 'Please connect your Shopify store to access SocialNest.');
    }

    /**
     * Check if request is caught in an infinite redirect loop.
     */
    protected function isRedirectLooping(Request $request, ?string $canonicalShop): bool
    {
        // Only track loops when a specific shop domain is being redirected
        if (empty($canonicalShop)) {
            return false;
        }

        $cacheKey = 'shopify_auth_loop_'.md5($canonicalShop.'_'.$request->path());
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            return true;
        }

        Cache::put($cacheKey, $attempts + 1, now()->addSeconds(15));

        return false;
    }

    /**
     * Terminate the redirect loop with an actionable diagnostic error screen.
     */
    protected function handleRedirectLoop(Request $request, ?string $canonicalShop): Response
    {
        Log::error('Shopify authentication redirect loop detected and prevented', [
            'shop' => $canonicalShop,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return response()->view('shopify.error', [
            'title' => 'Authentication Redirect Loop Prevented',
            'message' => 'SocialNest detected multiple consecutive redirect attempts for store '.($canonicalShop ?? 'unknown').'. This usually occurs when third-party cookies or cross-site framing are restricted by the browser in Shopify Admin.',
            'shop' => $canonicalShop,
            'action_url' => route('shopify.install', array_filter(['shop' => $canonicalShop, 'reauth' => 1])),
            'action_label' => 'Re-authenticate with Shopify',
        ], 429);
    }

    /**
     * Clear redirect loop counter when authentication succeeds.
     */
    protected function clearRedirectLoopCounter(?string $shop, Request $request): void
    {
        if (! empty($shop)) {
            Cache::forget('shopify_auth_loop_'.md5($shop.'_'.$request->path()));
        }
    }
}
