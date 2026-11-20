<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\ShopifyDashboardController;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\SocialLinkController;
use App\Http\Controllers\StorefrontApiController;
use App\Http\Controllers\StorefrontSettingsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\WishlistSetupController;
use App\Http\Controllers\WishlistStorefrontController;
use App\Http\Middleware\AuthenticateAdmin;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Entry points and application routes for SocialNest.
 * |
 */

// Root Entry Point - Public SaaS Marketing Landing Page or Shopify Launch Verification
Route::get('/', [ShopifyAuthController::class, 'appEntry'])
    ->middleware('shopify.csp')
    ->name('home');

Route::view('/terms-and-conditions', 'legal.terms-and-conditions')
    ->name('terms.conditions');

Route::view('/privacy-policy', 'legal.privacy-policy')
    ->name('privacy.policy');

// Public Support & Help Center
Route::get('/support', [SupportController::class, 'publicIndex'])
    ->name('support.public');

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

// Route::post('/shopify/logout', [ShopifyAuthController::class, 'logout'])
//     ->name('shopify.logout');

// Authenticated Embedded Dashboard & Merchant Management
Route::middleware(['shopify.csp', 'shopify.auth'])->group(function (): void {
    // GDPR Customer Data Export Retrieval (Authenticated Merchant Only)
    Route::get('/shopify/gdpr/export/{customerId}', [ShopifyWebhookController::class, 'downloadCustomerExport'])
        ->name('shopify.gdpr.export');

    // Fully Automatic Shopify App Pricing Subscription Cancellation
    Route::post('/shopify/subscription/cancel-active', [PlansController::class, 'cancelActiveSubscription'])
        ->name('shopify.subscription.cancel.active');
});

// Server-Rendered Embedded Page Views (Supports Shopify ID Token / App Bridge JWT & Launch HMAC)
Route::middleware(['shopify.csp', 'shopify.id_token', 'shopify.auth', 'shopify.subscription'])->group(function (): void {
    // Overview Dashboard
    Route::get('/shopify/dashboard', [ShopifyDashboardController::class, 'index'])
        ->name('shopify.dashboard');

    // Social Links Management Views
    Route::get('/shopify/social-links', [SocialLinkController::class, 'index'])
        ->name('shopify.social-links.index');

    Route::get('/shopify/social-links/create', [SocialLinkController::class, 'create'])
        ->name('shopify.social-links.create');

    Route::get('/shopify/social-links/{id}/edit', [SocialLinkController::class, 'edit'])
        ->name('shopify.social-links.edit');

    // Plans & Billing Views (Exempt from blocking inside CheckSubscription)
    Route::get('/shopify/plans', [PlansController::class, 'index'])
        ->name('shopify.plans.index');

    Route::get('/shopify/plans/checkout/{plan}', [PlansController::class, 'checkout'])
        ->name('shopify.plans.checkout');

    Route::get('/shopify/plans/status', [PlansController::class, 'status'])
        ->name('shopify.plans.status');

    Route::get('/shopify/billing/callback', [PlansController::class, 'billingCallback'])
        ->name('shopify.billing.callback');

    Route::get('/shopify/billing/welcome', [PlansController::class, 'welcome'])
        ->name('shopify.billing.welcome');

    Route::get('/shopify/pricing/welcome', [PlansController::class, 'welcome'])
        ->name('shopify.pricing.welcome');

    // Help & Support View
    Route::get('/shopify/support', [SupportController::class, 'index'])
        ->name('shopify.support.index');
});

// Premium-Only Embedded Views (Requires Active Pro Subscription)
Route::middleware(['shopify.csp', 'shopify.id_token', 'shopify.auth', 'shopify.subscription', 'shopify.pro'])->group(function (): void {
    // Storefront Floating Widget Settings View
    Route::get('/shopify/settings', [StorefrontSettingsController::class, 'index'])
        ->name('shopify.settings.index');
});

// Embedded Authenticated Action & API Endpoints (Protected by Shopify ID Token / App Bridge JWT)
Route::middleware(['shopify.csp', 'shopify.id_token', 'shopify.subscription'])->group(function (): void {
    // Live Dashboard Analytics API
    Route::get('/shopify/analytics', [ShopifyDashboardController::class, 'analytics'])
        ->name('shopify.analytics');

    // Activate Storefront Theme App Embed
    Route::get('/shopify/social-links/activate-embed', [
        SocialLinkController::class,
        'activateStorefrontEmbed',
    ])->name('shopify.social-links.activate-embed');

    // Social Links Management CRUD Actions
    Route::post('/shopify/social-links/discover-logos', [SocialLinkController::class, 'discoverLogos'])
        ->name('shopify.social-links.discover-logos');

    Route::post('/shopify/social-links', [SocialLinkController::class, 'store'])
        ->name('shopify.social-links.store');

    Route::put('/shopify/social-links/{id}', [SocialLinkController::class, 'update'])
        ->name('shopify.social-links.update');

    Route::post('/shopify/social-links/store-global-custom-app', [SocialLinkController::class, 'storeGlobalCustomApp'])
        ->name('shopify.social-links.store-global-custom-app');

    Route::match(['POST', 'PATCH'], '/shopify/social-links/{id}/toggle', [SocialLinkController::class, 'toggle'])
        ->name('shopify.social-links.toggle');

    Route::delete('/shopify/social-links/{id}', [SocialLinkController::class, 'destroy'])
        ->name('shopify.social-links.destroy');

    Route::post('/shopify/social-links/reorder', [SocialLinkController::class, 'reorder'])
        ->name('shopify.social-links.reorder');
});

// Premium-Only Action & API Endpoints (Requires Active Pro Subscription)
Route::middleware(['shopify.csp', 'shopify.id_token', 'shopify.subscription', 'shopify.pro'])->group(function (): void {
    // Storefront Floating Widget Settings Action
    Route::put('/shopify/settings', [StorefrontSettingsController::class, 'update'])
        ->name('shopify.settings.update');

    // Automated Wishlist Setup Action & Status
    Route::match(['GET', 'POST'], '/shopify/wishlist/setup', [WishlistSetupController::class, 'setup'])
        ->name('shopify.wishlist.setup');

    Route::get('/shopify/wishlist/status', [WishlistSetupController::class, 'status'])
        ->name('shopify.wishlist.status');
});

// Embedded Billing Action Endpoints (Exempt from CheckSubscription to allow subscribing)
Route::middleware(['shopify.csp', 'shopify.id_token'])->group(function (): void {
    Route::post('/shopify/plans/subscribe', [PlansController::class, 'subscribe'])
        ->name('shopify.plans.subscribe');

    Route::post('/shopify/plans/cancel', [PlansController::class, 'cancel'])
        ->name('shopify.plans.cancel');

    Route::post('/shopify/plans/resume', [PlansController::class, 'resume'])
        ->name('shopify.plans.resume');
});

// Public Storefront Configuration & Click Tracking API (Supports CORS Preflight OPTIONS)
Route::match(['GET', 'OPTIONS'], '/api/storefront/config', [StorefrontApiController::class, 'config'])
    ->name('api.storefront.config');

Route::match(['POST', 'OPTIONS'], '/api/storefront/track-click', [StorefrontApiController::class, 'trackClick'])
    ->name('api.storefront.track-click');

// Shopify Webhook Handling (Exempt from CSRF in bootstrap/app.php)
Route::post('/shopify/webhooks', [ShopifyWebhookController::class, 'handle'])
    ->name('shopify.webhooks');

// Shopify App Proxy Preflight CORS OPTIONS
Route::options('/shopify/proxy/{any?}', fn () => response('', 204, [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
    'Access-Control-Allow-Headers' => 'Content-Type, X-Guest-UUID, Authorization, Accept, X-Shopify-Shop-Domain',
    'Access-Control-Allow-Private-Network' => 'true',
]))->where('any', '.*');

Route::options('/api/storefront/wishlist/{any?}', fn () => response('', 204, [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
    'Access-Control-Allow-Headers' => 'Content-Type, X-Guest-UUID, Authorization, Accept, X-Shopify-Shop-Domain',
    'Access-Control-Allow-Private-Network' => 'true',
]))->where('any', '.*');

Route::options('/apps/{subpath}/{any?}', fn () => response('', 204, [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
    'Access-Control-Allow-Headers' => 'Content-Type, X-Guest-UUID, Authorization, Accept, X-Shopify-Shop-Domain',
    'Access-Control-Allow-Private-Network' => 'true',
]))->where('subpath', 'socialnest|socialvire')->where('any', '.*');

// Shopify App Proxy Storefront Wishlist API (Signed with Shopify HMAC, Rate Limited)
Route::middleware(['shopify.proxy', 'throttle:120,1'])->group(function (): void {
    // 1. Primary App Proxy URL: /shopify/proxy/wishlist/*
    Route::prefix('/shopify/proxy/wishlist')->name('proxy.wishlist.')->group(function (): void {
        Route::get('/', [WishlistStorefrontController::class, 'index'])->name('index');
        Route::get('/count', [WishlistStorefrontController::class, 'count'])->name('count');
        Route::get('/status', [WishlistStorefrontController::class, 'status'])->name('status');
        Route::post('/add', [WishlistStorefrontController::class, 'add'])->name('add');
        Route::match(['POST', 'DELETE'], '/remove', [WishlistStorefrontController::class, 'remove'])->name('remove');
        Route::post('/merge', [WishlistStorefrontController::class, 'merge'])->name('merge');
        Route::post('/migrate-local', [WishlistStorefrontController::class, 'migrateLocal'])->name('migrate-local');
    });

    // 2. Direct subpath forwarding fallback: /shopify/proxy/*
    Route::prefix('/shopify/proxy')->name('proxy.direct.')->group(function (): void {
        Route::match(['GET', 'OPTIONS'], '/config', [StorefrontApiController::class, 'config'])->name('config');
        Route::get('/', [WishlistStorefrontController::class, 'index'])->name('index');
        Route::get('/count', [WishlistStorefrontController::class, 'count'])->name('count');
        Route::get('/status', [WishlistStorefrontController::class, 'status'])->name('status');
        Route::post('/add', [WishlistStorefrontController::class, 'add'])->name('add');
        Route::match(['POST', 'DELETE'], '/remove', [WishlistStorefrontController::class, 'remove'])->name('remove');
        Route::post('/merge', [WishlistStorefrontController::class, 'merge'])->name('merge');
        Route::post('/migrate-local', [WishlistStorefrontController::class, 'migrateLocal'])->name('migrate-local');
    });

    // 3. API storefront alias: /api/storefront/wishlist/*
    Route::prefix('/api/storefront/wishlist')->name('api.wishlist.')->group(function (): void {
        Route::get('/', [WishlistStorefrontController::class, 'index'])->name('index');
        Route::get('/count', [WishlistStorefrontController::class, 'count'])->name('count');
        Route::get('/status', [WishlistStorefrontController::class, 'status'])->name('status');
        Route::post('/add', [WishlistStorefrontController::class, 'add'])->name('add');
        Route::match(['POST', 'DELETE'], '/remove', [WishlistStorefrontController::class, 'remove'])->name('remove');
        Route::post('/merge', [WishlistStorefrontController::class, 'merge'])->name('merge');
        Route::post('/migrate-local', [WishlistStorefrontController::class, 'migrateLocal'])->name('migrate-local');
    });

    // 4. App Proxy path prefixes: /apps/socialnest/* and /apps/socialvire/*
    foreach (['socialnest', 'socialvire'] as $subpath) {
        Route::prefix("/apps/{$subpath}")->name("proxy.{$subpath}.")->group(function (): void {
            Route::match(['GET', 'OPTIONS'], '/config', [StorefrontApiController::class, 'config'])->name('config');
            Route::get('/wishlist', [WishlistStorefrontController::class, 'index'])->name('wishlist.index');
            Route::get('/wishlist/count', [WishlistStorefrontController::class, 'count'])->name('wishlist.count');
            Route::get('/wishlist/status', [WishlistStorefrontController::class, 'status'])->name('wishlist.status');
            Route::post('/wishlist/add', [WishlistStorefrontController::class, 'add'])->name('wishlist.add');
            Route::match(['POST', 'DELETE'], '/wishlist/remove', [WishlistStorefrontController::class, 'remove'])->name('wishlist.remove');
            Route::post('/wishlist/merge', [WishlistStorefrontController::class, 'merge'])->name('wishlist.merge');
            Route::post('/wishlist/migrate-local', [WishlistStorefrontController::class, 'migrateLocal'])->name('wishlist.migrate-local');
        });
    }
});

// admin routes

/*
 * |--------------------------------------------------------------------------
 * | Admin Panel Routes
 * |--------------------------------------------------------------------------
 */

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Routes
    Route::get('/login', [AdminController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AdminController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');

    // Protected Admin Routes
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])
            ->name('dashboard');

        Route::post('/logout', [AdminController::class, 'logout'])
            ->name('logout');

        Route::get('/merchants', [MerchantController::class, 'index'])
            ->name('merchants.index');

        Route::get('/merchants/{merchant}', [MerchantController::class, 'show'])
            ->name('merchants.show');

        // Website Branding Settings
        Route::get('/settings/branding', [SiteSettingController::class, 'index'])
            ->name('settings.branding');

        Route::put('/settings/branding', [SiteSettingController::class, 'updateBranding'])
            ->name('settings.branding.update');

        // App Settings (Admin Profile & Password Management)
        Route::get('/settings/app', [AppSettingsController::class, 'edit'])
            ->name('settings.app');

        Route::put('/settings/app/profile', [AppSettingsController::class, 'updateProfile'])
            ->name('settings.app.profile');

        Route::put('/settings/app/password', [AppSettingsController::class, 'updatePassword'])
            ->name('settings.app.password');

        // Convenience Profile Alias
        Route::get('/profile', [AppSettingsController::class, 'edit'])
            ->name('profile');
    });
});
