<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifyStore;
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

    public function dashboard()
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

        return view('admin.dashboard', compact(
            'totalStores',
            'activeStores',
            'activeSubscriptions',
            'totalSocialLinks',
            'recentStores'
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
