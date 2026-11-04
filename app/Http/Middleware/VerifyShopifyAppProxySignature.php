<?php

namespace App\Http\Middleware;

use App\Models\ShopifyStore;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyAppProxySignature
{
    /**
     * Handle an incoming Shopify App Proxy request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->query('signature');
        $rawShop = $request->query('shop') ?? $request->header('X-Shopify-Shop-Domain') ?? $request->input('shop');
        $timestamp = $request->query('timestamp');

        Log::info('[WishlistProxy] Incoming request', [
            'path' => $request->path(),
            'method' => $request->method(),
            'has_signature' => ! empty($signature),
            'shop_param' => $rawShop,
        ]);

        // If this is a signed App Proxy request (Shopify sends signature in query string)
        if ($signature && is_string($signature)) {
            if (! $rawShop || ! is_string($rawShop)) {
                Log::warning('[WishlistProxy] Missing shop in signed App Proxy request');

                return $this->errorResponse('Missing shop domain in App Proxy request', 'MISSING_SHOP', 400);
            }

            // Validate timestamp to prevent replay attacks (allow up to 24 hours drift)
            if (! $timestamp || ! is_numeric($timestamp) || abs(time() - (int) $timestamp) > 86400) {
                Log::warning('[WishlistProxy] Invalid/expired timestamp in App Proxy request', ['timestamp' => $timestamp]);

                return $this->errorResponse('App Proxy request timestamp is expired or invalid', 'INVALID_TIMESTAMP', 401);
            }

            // Verify cryptographic HMAC signature
            $secret = config('shopify.api_secret') ?: env('SHOPIFY_API_SECRET');
            if (! $secret || ! is_string($secret)) {
                Log::error('[WishlistProxy] Shopify App Secret is not configured on server');

                return $this->errorResponse('Shopify App Secret is not configured on server', 'SERVER_CONFIG_ERROR', 500);
            }

            if (! $this->validateSignature($request->query(), $signature, $secret)) {
                Log::warning('[WishlistProxy] HMAC verification failed', ['shop' => $rawShop]);

                return $this->errorResponse('Invalid App Proxy signature verification failed', 'INVALID_SIGNATURE', 401);
            }

            // Verify that the shop exists and is currently active
            $cleanShop = strtolower(trim($rawShop));
            $store = ShopifyStore::where('shop', $cleanShop)
                ->where('is_active', true)
                ->first();

            if (! $store) {
                Log::warning('[WishlistProxy] Store not found or inactive', ['shop' => $cleanShop]);

                return $this->errorResponse("Shop '{$cleanShop}' not found or is currently inactive", 'STORE_NOT_FOUND', 404);
            }

            // Extract logged-in customer ID safely from verified Shopify context
            $rawCustomerId = $request->query('logged_in_customer_id');
            $customerId = null;
            if ($rawCustomerId !== null && trim((string) $rawCustomerId) !== '') {
                $customerId = (string) $rawCustomerId;
            }

            Log::info('[WishlistProxy] Signed App Proxy verified successfully', [
                'shop' => $store->shop,
                'is_customer' => $customerId !== null,
            ]);

            // Set verified attributes on request
            $request->attributes->set('shopify_store', $store);
            $request->attributes->set('shopify_shop', $store->shop);
            $request->attributes->set('shopify_customer_id', $customerId);
            $request->attributes->set('is_customer', $customerId !== null);

            return $next($request);
        }

        // Direct Storefront API route (/api/storefront/wishlist/*):
        // Allow secure guest access with shop validation when Shopify App Proxy is not routing
        if ($request->is('api/storefront/wishlist*')) {
            if (! $rawShop || ! is_string($rawShop)) {
                Log::warning('[WishlistProxy] Missing shop in storefront API request');

                return $this->errorResponse('Missing shop domain in storefront request', 'MISSING_SHOP', 400);
            }

            $cleanShop = strtolower(trim($rawShop));
            $store = ShopifyStore::where('shop', $cleanShop)
                ->where('is_active', true)
                ->first();

            if (! $store) {
                Log::warning('[WishlistProxy] Store not found or inactive for storefront API', ['shop' => $cleanShop]);

                return $this->errorResponse("Shop '{$cleanShop}' not found or is currently inactive", 'STORE_NOT_FOUND', 404);
            }

            Log::info('[WishlistProxy] Direct storefront API request authenticated for shop', [
                'shop' => $store->shop,
                'path' => $request->path(),
            ]);

            // Direct API requests cannot claim verified customer status without Shopify HMAC signature
            $request->attributes->set('shopify_store', $store);
            $request->attributes->set('shopify_shop', $store->shop);
            $request->attributes->set('shopify_customer_id', null);
            $request->attributes->set('is_customer', false);

            return $next($request);
        }

        Log::warning('[WishlistProxy] Missing App Proxy signature for proxy route', ['path' => $request->path()]);

        return $this->errorResponse('Missing App Proxy signature', 'MISSING_SIGNATURE', 401);
    }

    /**
     * Compute and compare HMAC-SHA256 signature for Shopify App Proxy requests.
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function validateSignature(array $queryParams, string $expectedSignature, string $secret): bool
    {
        unset($queryParams['signature']);
        ksort($queryParams);

        $signatureString = '';
        foreach ($queryParams as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $signatureString .= $key.'='.$val;
        }

        $calculatedSignature = hash_hmac('sha256', $signatureString, $secret);

        return hash_equals($calculatedSignature, $expectedSignature);
    }

    /**
     * Return standard JSON error response for storefront API.
     */
    protected function errorResponse(string $message, string $code, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ], $status, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Guest-UUID, Authorization, Accept',
        ]);
    }
}
