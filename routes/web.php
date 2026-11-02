<?php

use App\Http\Controllers\PlansController;
use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\ShopifyDashboardController;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\SocialLinkController;
use App\Http\Controllers\StorefrontApiController;
use App\Http\Controllers\StorefrontSettingsController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Entry points and application routes for SocialNest.
|
*/

// Root Entry Point - Public SaaS Marketing Landing Page or Shopify Launch Verification
Route::get('/', [ShopifyAuthController::class, 'appEntry'])
    ->middleware('shopify.csp')
    ->name('home');

// Shopify Installation & OAuth Flow
Route::match(['get', 'post'], '/shopify/install', [ShopifyAuthController::class, 'install'])
    ->middleware('shopify.csp')
    ->name('shopify.install');

Route::get('/shopify/callback', [ShopifyAuthController::class, 'callback'])
    ->middleware('shopify.csp')
    ->name('shopify.callback');

// Secondary callback alias to support /callback registered in default .env
Route::get('/callback', [ShopifyAuthController::class, 'callback'])
    ->middleware('shopify.csp')
    ->name('shopify.callback.alias');

Route::post('/shopify/logout', [ShopifyAuthController::class, 'logout'])
    ->name('shopify.logout');

// Authenticated Embedded Dashboard & Merchant Management
Route::middleware(['shopify.csp', 'shopify.auth', 'shopify.subscription'])->group(function (): void {
    // Overview Dashboard & Analytics
    Route::get('/shopify/dashboard', [ShopifyDashboardController::class, 'index'])
        ->name('shopify.dashboard');

    Route::get('/shopify/analytics', [ShopifyDashboardController::class, 'analytics'])
        ->name('shopify.analytics');

    // Social Links Management CRUD
    Route::get('/shopify/social-links', [SocialLinkController::class, 'index'])
        ->name('shopify.social-links.index');

    Route::get('/shopify/social-links/create', [SocialLinkController::class, 'create'])
        ->name('shopify.social-links.create');

    Route::post('/shopify/social-links/discover-logos', [SocialLinkController::class, 'discoverLogos'])
        ->name('shopify.social-links.discover-logos');

    Route::post('/shopify/social-links', [SocialLinkController::class, 'store'])
        ->name('shopify.social-links.store');

    Route::get('/shopify/social-links/{id}/edit', [SocialLinkController::class, 'edit'])
        ->name('shopify.social-links.edit');

    Route::put('/shopify/social-links/{id}', [SocialLinkController::class, 'update'])
        ->name('shopify.social-links.update');

    Route::match(['POST', 'PATCH'], '/shopify/social-links/{id}/toggle', [SocialLinkController::class, 'toggle'])
        ->name('shopify.social-links.toggle');

    Route::delete('/shopify/social-links/{id}', [SocialLinkController::class, 'destroy'])
        ->name('shopify.social-links.destroy');

    Route::post('/shopify/social-links/reorder', [SocialLinkController::class, 'reorder'])
        ->name('shopify.social-links.reorder');

    // Storefront Floating Widget Settings
    Route::get('/shopify/settings', [StorefrontSettingsController::class, 'index'])
        ->name('shopify.settings.index');

    Route::put('/shopify/settings', [StorefrontSettingsController::class, 'update'])
        ->name('shopify.settings.update');

    // Plans & Billing (Exempt from blocking inside CheckSubscription)
    Route::get('/shopify/plans', [PlansController::class, 'index'])
        ->name('shopify.plans.index');

    Route::get('/shopify/plans/checkout/{plan}', [PlansController::class, 'checkout'])
        ->name('shopify.plans.checkout');

    Route::post('/shopify/plans/subscribe', [PlansController::class, 'subscribe'])
        ->name('shopify.plans.subscribe');

    Route::post('/shopify/plans/cancel', [PlansController::class, 'cancel'])
        ->name('shopify.plans.cancel');

    Route::post('/shopify/plans/resume', [PlansController::class, 'resume'])
        ->name('shopify.plans.resume');

    Route::get('/shopify/plans/status', [PlansController::class, 'status'])
        ->name('shopify.plans.status');

    // Help & Support
    Route::get('/shopify/support', [SupportController::class, 'index'])
        ->name('shopify.support.index');
});

// Public Storefront Configuration & Click Tracking API (Supports CORS Preflight OPTIONS)
Route::match(['GET', 'OPTIONS'], '/api/storefront/config', [StorefrontApiController::class, 'config'])
    ->name('api.storefront.config');

Route::match(['POST', 'OPTIONS'], '/api/storefront/track-click', [StorefrontApiController::class, 'trackClick'])
    ->name('api.storefront.track-click');

// Shopify Webhook Handling (Exempt from CSRF in bootstrap/app.php)
Route::post('/shopify/webhooks', [ShopifyWebhookController::class, 'handle'])
    ->name('shopify.webhooks');
