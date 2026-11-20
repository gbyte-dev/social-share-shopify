<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifyStore;
use App\Models\ShopSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function dashboard(Request $request)
    {
        // Total registered Shopify stores
        $totalStores = ShopifyStore::count();

        // Currently installed and active stores
        $activeStores = ShopifyStore::where('is_active', true)
            ->whereNull('uninstalled_at')
            ->count();

        // Stores with a currently active subscription
        $activeSubscriptions = ShopifyStore::whereHas('activeSubscription')
            ->count();

        // Total social links across all stores
        $totalSocialLinks = ShopifyStore::withCount('socialLinks')
            ->get()
            ->sum('social_links_count');

        // Latest registered stores
        $recentStores = ShopifyStore::latest('created_at')
            ->limit(5)
            ->get([
                'id',
                'shop',
                'is_active',
                'installed_at',
                'uninstalled_at',
                'created_at',
            ]);

        // --- Analytics Chart 1: Merchant Registration Trend (7, 30, 90 days) ---
        $registrationCounts = ShopifyStore::where('created_at', '>=', now()->subDays(90)->startOfDay())
            ->selectRaw('DATE(created_at) as reg_date, COUNT(*) as count')
            ->groupBy('reg_date')
            ->pluck('count', 'reg_date')
            ->all();

        $registrationTrends = [];
        foreach ([7, 30, 90] as $days) {
            $labels = [];
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dayCarbon = now()->subDays($i);
                $dateKey = $dayCarbon->format('Y-m-d');
                $labels[] = $dayCarbon->format($days === 7 ? 'D, j M' : 'j M');
                $data[] = (int) ($registrationCounts[$dateKey] ?? 0);
            }

            $registrationTrends[$days] = [
                'labels' => $labels,
                'data' => $data,
                'total' => array_sum($data),
            ];
        }

        // --- Analytics Chart 2: Subscription Status Distribution ---
        $rawSubscriptionStatuses = ShopSubscription::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $totalSubscriptions = (int) array_sum($rawSubscriptionStatuses);

        $statusColorPalette = [
            ShopSubscription::STATUS_ACTIVE => '#008060',
            ShopSubscription::STATUS_CANCELLED => '#d72c0d',
            ShopSubscription::STATUS_EXPIRED => '#6d7175',
            ShopSubscription::STATUS_SUSPENDED => '#b45309',
        ];

        $fallbackColors = ['#4f46e5', '#0284c7', '#0d9488', '#ea580c', '#8b5cf6'];
        $colorIndex = 0;

        $subscriptionDistribution = [];
        foreach ($rawSubscriptionStatuses as $status => $count) {
            $color = $statusColorPalette[$status] ?? ($fallbackColors[$colorIndex++ % count($fallbackColors)]);
            $percentage = $totalSubscriptions > 0 ? round(($count / $totalSubscriptions) * 100, 1) : 0;

            $subscriptionDistribution[] = [
                'status' => (string) $status,
                'label' => ucfirst(str_replace('_', ' ', (string) $status)),
                'count' => (int) $count,
                'percentage' => $percentage,
                'color' => $color,
            ];
        }

        if ($request->wantsJson()) {
            return response()->json([
                'registrationTrends' => $registrationTrends,
                'subscriptionDistribution' => $subscriptionDistribution,
                'totalSubscriptions' => $totalSubscriptions,
            ]);
        }

        return view('admin.dashboard', compact(
            'totalStores',
            'activeStores',
            'activeSubscriptions',
            'totalSocialLinks',
            'recentStores',
            'registrationTrends',
            'subscriptionDistribution',
            'totalSubscriptions'
        ));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
