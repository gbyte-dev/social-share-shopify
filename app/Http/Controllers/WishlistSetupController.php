<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WishlistSetupController extends Controller
{
    /**
     * Complete SocialNest Wishlist setup by verifying or creating /pages/wishlist
     * and redirecting the merchant to the Shopify Theme Editor with the app block pre-selected.
     */
    public function setup(Request $request, ShopifyService $shopifyService): RedirectResponse|JsonResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $target = (string) $request->input('target', $request->query('target', 'mainSection'));
        $deepLinkUrl = $shopifyService->getWishlistThemeEditorDeepLink($store->shop, $target);
        $reauthUrl = route('shopify.install', ['shop' => $store->shop]);

        try {
            $result = $shopifyService->createWishlistPage($store);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'created' => $result['created'],
                    'requires_reauth' => false,
                    'message' => $result['message'],
                    'page' => $result['page'],
                    'deep_link_url' => $deepLinkUrl,
                ]);
            }

            return redirect()->away($deepLinkUrl);
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::warning('Could not automatically create/verify Wishlist page via GraphQL', [
                'shop' => $store->shop,
                'target' => $target,
                'error' => $errorMsg,
            ]);

            // When missing write_content scope or 403 forbidden, gracefully provide deep link + reauth option
            $isPermissionIssue = str_contains($errorMsg, 'Access denied')
                || str_contains($errorMsg, '403')
                || str_contains($errorMsg, 'permission');

            $message = $isPermissionIssue
                ? 'Theme Editor is opening! Your store needs updated page permissions to auto-create /pages/wishlist. Please approve permissions or create /pages/wishlist under Online Store → Pages.'
                : 'Theme Editor is opening! Please ensure /pages/wishlist exists in Online Store → Pages.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'created' => false,
                    'requires_reauth' => $isPermissionIssue,
                    'reauth_url' => $reauthUrl,
                    'message' => $message,
                    'deep_link_url' => $deepLinkUrl,
                ]);
            }

            return redirect()->away($deepLinkUrl);
        }
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
