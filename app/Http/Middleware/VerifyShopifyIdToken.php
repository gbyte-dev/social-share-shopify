<?php

namespace App\Http\Middleware;

use App\Models\ShopifyStore;
use Closure;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyIdToken
{
    /**
     * Handle an incoming request by verifying the Shopify App Bridge session ID token (JWT).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');

        // 1. If an Authorization Bearer header is present, STRICTLY verify the Shopify session ID token
        if (! empty($authHeader) && preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            $apiSecret = (string) config('shopify.api_secret');
            $apiKey = (string) config('shopify.api_key');

            if (empty($apiSecret) || empty($apiKey)) {
                Log::critical('VerifyShopifyIdToken: Shopify API credentials missing from configuration.');

                return $this->unauthorizedResponse('Shopify API credentials configuration error.');
            }

            // Apply a 10-second leeway to accommodate clock drift between Shopify and the app server
            JWT::$leeway = 10;

            try {
                $payload = JWT::decode($token, new Key($apiSecret, 'HS256'));
            } catch (ExpiredException $e) {
                Log::warning('VerifyShopifyIdToken: Session token has expired.', [
                    'route' => $request->route()?->getName(),
                    'error' => $e->getMessage(),
                ]);

                return $this->unauthorizedResponse('Shopify session token has expired.');
            } catch (SignatureInvalidException) {
                Log::warning('VerifyShopifyIdToken: Invalid session token signature.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Invalid Shopify session token signature.');
            } catch (BeforeValidException) {
                Log::warning('VerifyShopifyIdToken: Session token not yet valid.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Shopify session token is not yet valid.');
            } catch (\Throwable $e) {
                Log::warning('VerifyShopifyIdToken: Session token decode failed.', [
                    'route' => $request->route()?->getName(),
                    'error_type' => get_class($e),
                ]);

                return $this->unauthorizedResponse('Malformed Shopify session token.');
            }

            // Validate Audience ('aud') matches configured Shopify Client ID / API Key
            if (! isset($payload->aud) || (string) $payload->aud !== $apiKey) {
                Log::warning('VerifyShopifyIdToken: Session token audience mismatch.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Session token audience mismatch.');
            }

            // Validate Destination ('dest') claim
            if (empty($payload->dest) || ! is_string($payload->dest)) {
                Log::warning('VerifyShopifyIdToken: Missing dest claim.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Missing destination claim in session token.');
            }

            $destHost = parse_url($payload->dest, PHP_URL_HOST);
            if (! $destHost) {
                Log::warning('VerifyShopifyIdToken: Unparseable dest URL.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Invalid destination URL in session token.');
            }

            $destHost = strtolower($destHost);

            // Validate destination is a legitimate *.myshopify.com domain
            if (! preg_match('/^[a-z0-9][a-z0-9\-]*\.myshopify\.com$/', $destHost)) {
                Log::warning('VerifyShopifyIdToken: Non-myshopify destination domain.', [
                    'route' => $request->route()?->getName(),
                    'dest_host' => $destHost,
                ]);

                return $this->unauthorizedResponse('Destination domain is not a valid myshopify.com store.');
            }

            // Validate Issuer ('iss') claim consistency with destination
            if (empty($payload->iss) || ! is_string($payload->iss)) {
                Log::warning('VerifyShopifyIdToken: Missing iss claim.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Missing issuer claim in session token.');
            }

            $issHost = parse_url($payload->iss, PHP_URL_HOST);
            if (! $issHost) {
                Log::warning('VerifyShopifyIdToken: Unparseable iss URL.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Invalid issuer URL in session token.');
            }

            $issHost = strtolower($issHost);
            $destHandle = explode('.', $destHost)[0];

            if ($issHost === 'admin.shopify.com') {
                // Unified Admin format: https://admin.shopify.com/store/{handle}
                $issPath = parse_url($payload->iss, PHP_URL_PATH) ?? '';
                if (! preg_match('/^\/store\/([a-z0-9\-]+)/i', $issPath, $issMatches) || strtolower($issMatches[1]) !== $destHandle) {
                    Log::warning('VerifyShopifyIdToken: Issuer handle does not match destination store handle.', [
                        'route' => $request->route()?->getName(),
                    ]);

                    return $this->unauthorizedResponse('Issuer handle does not match destination store handle.');
                }
            } elseif ($issHost === $destHost) {
                // Direct Admin format: https://{shop}.myshopify.com/admin
                $issPath = parse_url($payload->iss, PHP_URL_PATH) ?? '';
                if (! str_starts_with(rtrim($issPath, '/'), '/admin') && $issPath !== '') {
                    Log::warning('VerifyShopifyIdToken: Invalid direct admin issuer path.', [
                        'route' => $request->route()?->getName(),
                    ]);

                    return $this->unauthorizedResponse('Invalid direct admin issuer path.');
                }
            } else {
                Log::warning('VerifyShopifyIdToken: Issuer host mismatch with destination host.', [
                    'route' => $request->route()?->getName(),
                ]);

                return $this->unauthorizedResponse('Issuer host mismatch with destination host.');
            }

            // Query shop spoofing check: strictly derive identity from dest; reject spoofed query/input shop
            $queryShop = $request->query('shop') ?? $request->input('shop');
            if (! empty($queryShop)) {
                $normalizedQueryShop = strtolower(trim((string) $queryShop));
                if ($normalizedQueryShop !== $destHost) {
                    Log::warning('VerifyShopifyIdToken: Query shop parameter spoofing attempt detected.', [
                        'route' => $request->route()?->getName(),
                        'query_shop' => $normalizedQueryShop,
                        'token_dest' => $destHost,
                    ]);

                    return $this->unauthorizedResponse('Shop parameter does not match authenticated session token.');
                }
            }

            // Resolve active installed ShopifyStore for the verified destination domain
            /** @var ShopifyStore|null $store */
            $store = ShopifyStore::where('shop', $destHost)
                ->where('is_active', true)
                ->whereNotNull('access_token')
                ->first();

            if (! $store || ! $store->isInstalled()) {
                Log::warning('VerifyShopifyIdToken: Store not found or inactive for verified domain.', [
                    'route' => $request->route()?->getName(),
                    'shop' => $destHost,
                ]);

                return $this->unauthorizedResponse('Store not installed or inactive.');
            }

            // Attach resolved store and token metadata to request attributes
            $request->attributes->set('shopify_store', $store);
            $request->attributes->set('shopify_shop', $destHost);
            $request->attributes->set('shopify_jwt_payload', (array) $payload);

            if (isset($payload->sub)) {
                $request->attributes->set('shopify_user_id', $payload->sub);
            }

            // Synchronize session if request is within session state
            if ($request->hasSession()) {
                session([
                    'shopify_shop' => $store->shop,
                    'shopify_store_id' => $store->id,
                ]);
            }

            return $next($request);
        }

        // 2. If NO Bearer token is provided:

        // A. Protected requests: X-Shopify-Navigation, AJAX, or JSON endpoints MUST require a valid Bearer token.
        // A Laravel session must NEVER override a missing or invalid Bearer token for these requests.
        $isProtectedRequest = $request->hasHeader('X-Shopify-Navigation')
            || $request->ajax()
            || $request->expectsJson()
            || $request->isXmlHttpRequest();

        if ($isProtectedRequest) {
            Log::warning('VerifyShopifyIdToken: Missing Authorization Bearer token on protected navigation/API endpoint.', [
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return $this->unauthorizedResponse('Missing or malformed Authorization Bearer session token.');
        }

        // B. Legitimate initial Shopify launch requests:
        // Pass to EnsureShopifyShopInstalled for cryptographic HMAC verification
        if ($request->has('hmac')) {
            return $next($request);
        }

        // C. Native document session fallback:
        // Only permitted for non-AJAX, non-JSON document requests with an active Laravel session
        if ($request->hasSession() && session('shopify_shop')) {
            $sessionShop = (string) session('shopify_shop');
            $sessionStoreId = session('shopify_store_id');
            $queryShop = $request->query('shop') ?? $request->input('shop');

            // If a shop parameter is explicitly provided, validate it matches the session shop identity
            if (! empty($queryShop)) {
                $normalizedQueryShop = strtolower(trim((string) $queryShop));
                if ($normalizedQueryShop !== strtolower($sessionShop)) {
                    Log::warning('VerifyShopifyIdToken: Cross-shop mismatch between session and query parameter on document reload.', [
                        'route' => $request->route()?->getName(),
                        'query_shop' => $normalizedQueryShop,
                        'session_shop' => $sessionShop,
                    ]);

                    return response()->view('shopify.error', [
                        'title' => 'Authentication Mismatch',
                        'message' => 'The requested shop does not match your active session. Please reload from Shopify Admin.',
                        'shop' => $normalizedQueryShop,
                    ], 403);
                }
            }

            /** @var ShopifyStore|null $store */
            $store = ShopifyStore::where('shop', $sessionShop)
                ->when($sessionStoreId, fn ($q) => $q->where('id', $sessionStoreId))
                ->where('is_active', true)
                ->whereNotNull('access_token')
                ->first();

            if ($store && $store->isInstalled()) {
                $request->attributes->set('shopify_store', $store);
                $request->attributes->set('shopify_shop', $store->shop);

                return $next($request);
            }
        }

        // D. For unauthenticated traditional browser navigation without valid session, pass through so EnsureShopifyShopInstalled can handle loop detection and install redirect
        return $next($request);
    }

    /**
     * Return standard 401 response with App Bridge retry header.
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401, [
            'X-Shopify-Retry-Invalid-Session-Request' => '1',
            'WWW-Authenticate' => 'Bearer error="invalid_token", error_description="'.addslashes($message).'"',
        ]);
    }
}
