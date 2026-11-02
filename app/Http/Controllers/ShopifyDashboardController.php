<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Services\AnalyticsService;
use App\Services\SocialPlatformService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopifyDashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Resolve the current authenticated ShopifyStore instance.
     */
    protected function resolveStore(Request $request): ?ShopifyStore
    {
        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store || ! $store->isInstalled()) {
            $sessionShop = session('shopify_shop');
            $sessionStoreId = session('shopify_store_id');

            if ($sessionShop && $sessionStoreId) {
                $store = ShopifyStore::where('shop', $sessionShop)
                    ->where('id', $sessionStoreId)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();
            }
        }

        return ($store && $store->isInstalled()) ? $store : null;
    }

    /**
     * Display the authenticated SocialNest embedded dashboard.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $store = $this->resolveStore($request);

        if (! $store) {
            return redirect()->route('shopify.install')
                ->with('error', 'Authentication required to access the SocialNest dashboard.');
        }

        $shop = $store->shop;
        $host = $request->query('host') ?? session('shopify_host');

        if ($host) {
            session(['shopify_host' => $host]);
        }

        $apiKey = config('shopify.api_key');
        $scopes = array_filter(array_map('trim', explode(',', $store->scope ?? config('shopify.api_scopes', ''))));

        // Real database-backed summary statistics
        $totalLinks = $store->socialLinks()->count();
        $activeLinks = $store->socialLinks()->where('is_active', true)->count();
        $inactiveLinks = $store->socialLinks()->where('is_active', false)->count();
        $totalPlatforms = count(SocialPlatformService::keys());

        // Recent social links
        $recentLinks = $store->socialLinks()->take(5)->get();

        // Storefront settings
        $settings = $store->getStorefrontSettings();

        // Comprehensive database-backed click tracking & dashboard analytics
        $analyticsData = $this->analyticsService->getDashboardAnalytics($store, $request->all());

        // Legacy summary fields for backward compatibility
        $totalClicks = $analyticsData['summary']['total_clicks'] ?? 0;
        $todayClicks = $analyticsData['summary']['clicks_today'] ?? 0;
        $platformClicks = [];
        foreach ($analyticsData['platform_chart']['platforms'] as $item) {
            $platformClicks[$item['platform']] = $item['clicks'];
        }

        return view('shopify.dashboard', [
            'store' => $store,
            'shop' => $shop,
            'host' => $host,
            'apiKey' => $apiKey,
            'scopes' => $scopes,
            'totalLinks' => $totalLinks,
            'activeLinks' => $activeLinks,
            'inactiveLinks' => $inactiveLinks,
            'totalPlatforms' => $totalPlatforms,
            'recentLinks' => $recentLinks,
            'settings' => $settings,
            'totalClicks' => $totalClicks,
            'todayClicks' => $todayClicks,
            'platformClicks' => $platformClicks,
            'analyticsData' => $analyticsData,
        ]);
    }

    /**
     * Return live JSON analytics data for dashboard AJAX filter changes.
     */
    public function analytics(Request $request): JsonResponse
    {
        $store = $this->resolveStore($request);

        if (! $store) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized or store not found',
            ], 401);
        }

        $analytics = $this->analyticsService->getDashboardAnalytics($store, $request->all());

        return response()->json($analytics, 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
