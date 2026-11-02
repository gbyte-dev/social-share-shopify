<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Display the Help & Support page.
     */
    public function index(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        return view('shopify.support.index', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
        ]);
    }

    /**
     * Resolve and strictly verify the authenticated Shopify store.
     */
    protected function resolveStore(Request $request): ShopifyStore
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

            if (! $store || ! $store->isInstalled()) {
                throw new HttpResponseException(
                    redirect()->route('shopify.install')->with('error', 'Authentication required.')
                );
            }
        }

        return $store;
    }
}
