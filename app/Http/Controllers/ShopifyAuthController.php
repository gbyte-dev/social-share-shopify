<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ShopifyAuthController extends Controller
{
    public function __construct(
        protected ShopifyService $shopifyService
    ) {}

    /**
     * App Entry point for root URL (/).
     *
     * Serves the public SaaS marketing landing page for regular visitors.
     * When launched from Shopify Admin (with shop parameter / launch context),
     * verifies store installation and redirects directly to dashboard or installation flow.
     */
    public function appEntry(Request $request): Response|RedirectResponse|View
    {
        $rawShop = $request->query('shop') ?? $request->header('X-Shopify-Shop-Domain');

        // If no Shopify store context is provided, render the public marketing landing page
        if (empty($rawShop)) {
            return view('welcome');
        }

        $canonicalShop = $this->shopifyService->normalizeShopDomain($rawShop);

        // Reject invalid shop domain format immediately
        if (! $canonicalShop) {
            return response()->view('shopify.error', [
                'title' => 'Invalid Store Domain',
                'message' => 'Invalid Shopify store domain. Please provide a valid myshopify.com address.',
                'shop' => $rawShop,
            ], 400);
        }

        // Validate API credentials before proceeding
        try {
            $this->shopifyService->validateCredentials();
        } catch (RuntimeException $e) {
            return response()->view('shopify.error', [
                'title' => 'Configuration Error',
                'message' => $e->getMessage(),
                'shop' => $canonicalShop,
            ], 500);
        }

        // Check if Shopify HMAC signature is present and valid
        $hasHmac = $request->has('hmac');
        $isHmacValid = $hasHmac && $this->shopifyService->verifyHmac($request->all());

        // If an HMAC was provided but failed verification, reject the request
        if ($hasHmac && ! $isHmacValid) {
            return response()->view('shopify.error', [
                'title' => 'Invalid Launch Signature',
                'message' => 'Invalid launch signature. The request could not be authenticated by Shopify.',
                'shop' => $canonicalShop,
            ], 403);
        }

        // Check if the current session is already authenticated for this exact store
        $isSessionAuthenticated = session('shopify_shop') === $canonicalShop && session('shopify_store_id');

        // Verify request authenticity: requires either a valid Shopify HMAC or an active session
        $isVerified = $isHmacValid || $isSessionAuthenticated;

        // Check whether SocialNest is installed and active for this exact store
        /** @var ShopifyStore|null $store */
        $store = ShopifyStore::where('shop', $canonicalShop)
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->first();

        $isInstalled = $store && $store->isInstalled();

        // 1. If verified and successfully installed, open the authenticated dashboard directly
        if ($isVerified && $isInstalled) {
            $host = $request->query('host');
            if (empty($host) && $canonicalShop) {
                $storeHandle = str_replace('.myshopify.com', '', $canonicalShop);
                $host = base64_encode("admin.shopify.com/store/{$storeHandle}");
            }

            session([
                'shopify_shop' => $store->shop,
                'shopify_store_id' => $store->id,
                'shopify_host' => $host,
            ]);

            Log::info('Shopify launch verified and installed, directing to dashboard', [
                'route' => 'appEntry',
                'shop' => $canonicalShop,
                'has_hmac' => $hasHmac,
            ]);

            return redirect()->route('shopify.dashboard', array_filter([
                'shop' => $canonicalShop,
                'host' => $host,
            ]));
        }

        // 2. If the store is not installed or unverified, initiate or resume the installation flow
        Log::info('Shopify launch store not installed or unverified, initiating OAuth', [
            'route' => 'appEntry',
            'shop' => $canonicalShop,
            'is_installed' => $isInstalled,
            'is_verified' => $isVerified,
        ]);

        return $this->startOAuthFlow($request, $canonicalShop);
    }

    /**
     * Installation entry point.
     * Displays domain prompt or begins OAuth flow with Shopify.
     */
    public function install(Request $request): Response|RedirectResponse
    {
        $rawShop = $request->query('shop') ?? $request->input('shop');

        if (empty($rawShop)) {
            return response()->view('shopify.install', [
                'error' => session('error'),
                'warning' => session('warning'),
                'success' => session('success'),
            ]);
        }

        $canonicalShop = $this->shopifyService->normalizeShopDomain($rawShop);

        if (! $canonicalShop) {
            return redirect()->route('shopify.install')
                ->with('error', 'Invalid store domain. Please provide a valid myshopify.com address (e.g. your-store.myshopify.com).');
        }

        // Validate API credentials before proceeding
        try {
            $this->shopifyService->validateCredentials();
        } catch (RuntimeException $e) {
            return response()->view('shopify.install', [
                'error' => $e->getMessage(),
                'shop' => $canonicalShop,
            ], 500);
        }

        // Check if the current session is already authenticated for this exact store
        $sessionShop = session('shopify_shop');
        $sessionStoreId = session('shopify_store_id');
        $isSessionAuthenticated = $sessionShop === $canonicalShop && ! empty($sessionStoreId);

        // Check if store is installed and active in database
        $existingStore = ShopifyStore::where('shop', $canonicalShop)
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->first();

        $isInstalled = $existingStore && $existingStore->isInstalled();

        // Check if any configured required scopes are missing from the store's granted scopes
        $configuredScopes = array_filter(array_map('trim', explode(',', (string) config('shopify.api_scopes'))));
        $grantedScopes = array_filter(array_map('trim', explode(',', (string) ($existingStore?->scope ?? ''))));
        $hasMissingScopes = ! empty(array_diff($configuredScopes, $grantedScopes));

        // If the store is already installed, has all required scopes, and is not requesting reauth, bind session and send to dashboard
        if ($isInstalled && ! $request->has('reauth') && ! $hasMissingScopes) {
            session([
                'shopify_shop' => $existingStore->shop,
                'shopify_store_id' => $existingStore->id,
            ]);

            Log::info('Shopify auth state: installed_authenticated', [
                'route' => 'shopify.install',
                'shop' => $canonicalShop,
                'destination' => 'shopify.dashboard',
            ]);

            return redirect()->route('shopify.dashboard', array_filter([
                'shop' => $canonicalShop,
                'host' => $request->query('host'),
            ]));
        }

        // Store is not installed or session is unauthenticated: initiate OAuth flow
        Log::info('Shopify auth state: initiating_oauth', [
            'route' => 'shopify.install',
            'shop' => $canonicalShop,
            'is_installed' => $isInstalled ? 'yes' : 'no',
            'session_authenticated' => $isSessionAuthenticated ? 'yes' : 'no',
        ]);

        return $this->startOAuthFlow($request, $canonicalShop);
    }

    /**
     * Generate cryptographically secure OAuth state and redirect to Shopify authorization.
     */
    protected function startOAuthFlow(Request $request, string $canonicalShop): Response|RedirectResponse
    {
        // Generate cryptographically secure nonce
        $state = Str::random(40);

        // Cache state for 15 minutes to prevent replay and CSRF attacks
        Cache::put("shopify_oauth_state_{$state}", [
            'shop' => $canonicalShop,
            'host' => $request->query('host'),
        ], now()->addMinutes(15));

        $authUrl = $this->shopifyService->getAuthorizeUrl($canonicalShop, $state);

        // Break out of iframe if loaded embedded inside Shopify Admin
        if ($request->query('embedded') === '1' || $request->header('Sec-Fetch-Dest') === 'iframe') {
            return response(
                '<!DOCTYPE html><html><head><title>Redirecting to Shopify...</title>'.
                '<script>window.top.location.href = '.json_encode($authUrl, JSON_UNESCAPED_SLASHES).';</script></head>'.
                '<body>Redirecting to Shopify authorization...</body></html>'
            );
        }

        return redirect()->away($authUrl);
    }

    /**
     * Process Shopify OAuth callback after merchant approves the installation.
     */
    public function callback(Request $request): Response|RedirectResponse
    {
        $code = $request->query('code');
        $rawShop = $request->query('shop');
        $state = $request->query('state');
        $hmac = $request->query('hmac');

        // Check for required parameters
        if (empty($code) || empty($rawShop) || empty($state) || empty($hmac)) {
            return response()->view('shopify.error', [
                'title' => 'Missing Parameters',
                'message' => 'The callback request from Shopify was missing required parameters.',
            ], 400);
        }

        // Validate shop domain strictly
        $canonicalShop = $this->shopifyService->normalizeShopDomain($rawShop);
        if (! $canonicalShop) {
            return response()->view('shopify.error', [
                'title' => 'Invalid Store Domain',
                'message' => 'The store domain received in callback is invalid or malformed.',
            ], 400);
        }

        // Verify HMAC signature to prevent forged requests
        if (! $this->shopifyService->verifyHmac($request->query())) {
            return response()->view('shopify.error', [
                'title' => 'Verification Failed',
                'message' => 'The HMAC signature could not be verified. This request did not originate from Shopify.',
            ], 400);
        }

        // Verify state nonce against cache to prevent CSRF / replay attacks
        $cachedState = Cache::pull("shopify_oauth_state_{$state}");
        if (! $cachedState || ! is_array($cachedState) || ($cachedState['shop'] ?? null) !== $canonicalShop) {
            return response()->view('shopify.error', [
                'title' => 'Invalid Session State',
                'message' => 'The authorization state has expired or is invalid. Please try installing SocialNest again.',
            ], 400);
        }

        // Perform token exchange with Shopify
        try {
            $tokenData = $this->shopifyService->exchangeCodeForAccessToken($canonicalShop, $code);
        } catch (RuntimeException $e) {
            return response()->view('shopify.error', [
                'title' => 'Token Exchange Failed',
                'message' => 'Failed to obtain access token from Shopify: '.$e->getMessage(),
            ], 502);
        }

        // Idempotently store authenticated merchant identity
        $store = $this->shopifyService->persistInstalledStore(
            $canonicalShop,
            $tokenData['access_token'],
            $tokenData['scope']
        );

        // Bind user session
        session([
            'shopify_shop' => $store->shop,
            'shopify_store_id' => $store->id,
        ]);

        $host = $request->query('host') ?? ($cachedState['host'] ?? null);
        if (empty($host) && $canonicalShop) {
            $storeHandle = str_replace('.myshopify.com', '', $canonicalShop);
            $host = base64_encode("admin.shopify.com/store/{$storeHandle}");
        }

        // Redirect to authenticated dashboard
        return redirect()->route('shopify.dashboard', array_filter([
            'shop' => $canonicalShop,
            'host' => $host,
        ]))->with('success', 'SocialNest installed successfully!');
    }

    /**
     * Clear the merchant session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $shop = session('shopify_shop');
        $request->session()->forget(['shopify_shop', 'shopify_store_id']);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'You have been logged out of SocialNest.');
    }
}
