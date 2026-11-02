<?php

namespace App\Http\Middleware;

use App\Services\ShopifyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetShopifyCspHeaders
{
    public function __construct(
        protected ShopifyService $shopifyService
    ) {}

    /**
     * Handle an incoming request.
     *
     * In an embedded Shopify application, Shopify loads the app within an iframe
     * in Shopify Admin (admin.shopify.com and *.myshopify.com).
     * To prevent framing by arbitrary malicious origins while allowing Shopify Admin,
     * we dynamically set Content-Security-Policy: frame-ancestors scoped specifically
     * to the active store domain and admin.shopify.com.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $rawShop = $request->query('shop') ?? session('shopify_shop');
        $canonicalShop = $this->shopifyService->normalizeShopDomain($rawShop);

        $isShopifyContext = ! empty($canonicalShop)
            || $request->query('embedded') === '1'
            || $request->is('shopify/*');

        if ($isShopifyContext) {
            $shopDomain = $canonicalShop ?: '*.myshopify.com';
            $csp = "frame-ancestors https://{$shopDomain} https://admin.shopify.com;";

            $response->headers->remove('X-Frame-Options');
            $response->headers->set('Content-Security-Policy', $csp);
        } else {
            // Public marketing visitor: preserve strict clickjacking protection
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self';");
        }

        return $response;
    }
}
