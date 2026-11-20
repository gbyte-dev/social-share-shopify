<?php

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureProSubscription;
use App\Http\Middleware\EnsureShopifyShopInstalled;
use App\Http\Middleware\SetShopifyCspHeaders;
use App\Http\Middleware\VerifyShopifyAppProxySignature;
use App\Http\Middleware\VerifyShopifyIdToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'shopify.auth' => EnsureShopifyShopInstalled::class,
            'shopify.id_token' => VerifyShopifyIdToken::class,
            'shopify.csp' => SetShopifyCspHeaders::class,
            'shopify.subscription' => CheckSubscription::class,
            'shopify.pro' => EnsureProSubscription::class,
            'shopify.proxy' => VerifyShopifyAppProxySignature::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'shopify/webhooks',
            'shopify/proxy/*',
            'api/storefront/*',
            'shopify/analytics',
            'shopify/social-links*',
            'shopify/settings',
            'shopify/plans/subscribe',
            'shopify/plans/cancel',
            'shopify/plans/resume',
            'shopify/wishlist/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
