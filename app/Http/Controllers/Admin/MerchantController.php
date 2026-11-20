<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifyStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantController extends Controller
{
    /**
     * Display a listing of the merchants or return DataTables JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $totalMerchantsCount = ShopifyStore::count();

        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            $query = ShopifyStore::query()
                ->with('activeSubscription.plan');

            // Search by Shopify domain or ID
            $search = $request->input('search');
            $searchVal = is_array($search) ? ($search['value'] ?? null) : $search;

            if (filled($searchVal)) {
                $query->where(function ($q) use ($searchVal) {
                    $q->where('shop', 'like', "%{$searchVal}%")
                        ->orWhere('id', 'like', "%{$searchVal}%");
                });
            }

            // Filter by installation status
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true)
                    ->whereNull('uninstalled_at');
            } elseif ($status === 'uninstalled') {
                $query->whereNotNull('uninstalled_at');
            }

            $recordsFiltered = (clone $query)->count();

            // Order mapping
            $orderColumnIndex = (int) $request->input('order.0.column', 4);
            $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            match ($orderColumnIndex) {
                0 => $query->orderBy('shop', $orderDir),
                4 => $query->orderBy('installed_at', $orderDir),
                default => $query->orderBy('created_at', $orderDir),
            };

            // Pagination
            $start = max(0, (int) $request->input('start', 0));
            $length = max(1, min(100, (int) $request->input('length', 10)));

            $merchants = $query->skip($start)->take($length)->get();

            $data = $merchants->map(function (ShopifyStore $merchant) {
                $subscription = $merchant->activeSubscription;
                $isUninstalled = $merchant->uninstalled_at !== null;
                $isActive = $merchant->is_active && ! $isUninstalled;

                $storeHtml = '<div class="store-name">'.e($merchant->shop).'</div>'
                    .'<div class="store-domain">Merchant ID: #'.e($merchant->id).'</div>';

                $planHtml = e($subscription?->plan?->name ?? 'No active plan');

                if ($isUninstalled) {
                    $accountStatusHtml = '<span class="status-badge status-uninstalled">Uninstalled</span>';
                } elseif ($isActive) {
                    $accountStatusHtml = '<span class="status-badge status-active">Active</span>';
                } else {
                    $accountStatusHtml = '<span class="status-badge status-inactive">Inactive</span>';
                }

                if ($subscription) {
                    $subscriptionHtml = '<span class="status-badge status-active">Active</span>';
                } else {
                    $subscriptionHtml = '<span class="status-badge status-inactive">No Active Subscription</span>';
                }

                $installedOnHtml = e($merchant->installed_at?->format('d M Y') ?? 'N/A');

                $actionHtml = '<a href="'.route('admin.merchants.show', $merchant).'" class="view-btn">View Details</a>';

                return [
                    'store' => $storeHtml,
                    'plan' => $planHtml,
                    'account_status' => $accountStatusHtml,
                    'subscription' => $subscriptionHtml,
                    'installed_on' => $installedOnHtml,
                    'action' => $actionHtml,
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalMerchantsCount,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        return view('admin.merchants.index', compact('totalMerchantsCount'));
    }

    public function show(ShopifyStore $merchant): View
    {
        $merchant->load('activeSubscription.plan');

        return view('admin.merchants.show', compact('merchant'));
    }
}
