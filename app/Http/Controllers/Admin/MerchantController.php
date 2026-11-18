<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifyStore;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request)
    {
        $query = ShopifyStore::query()
            ->with('activeSubscription.plan');

        // Search by Shopify domain
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where('shop', 'like', "%{$search}%");
        }

        // Filter by installation status
        if ($request->status === 'active') {
            $query->where('is_active', true)
                ->whereNull('uninstalled_at');
        } elseif ($request->status === 'uninstalled') {
            $query->whereNotNull('uninstalled_at');
        }

        $merchants = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.merchants.index', compact('merchants'));
    }

    public function show(ShopifyStore $merchant)
    {
        $merchant->load('activeSubscription.plan');

        return view('admin.merchants.show', compact('merchant'));
    }
}
