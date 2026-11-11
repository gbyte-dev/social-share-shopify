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
        $reauthUrl = route('shopify.install', ['shop' => $store->shop]);
        $deepLinkUrl = null;

        try {
            $result = $shopifyService->createWishlistPage($store);

            $deepLinkUrl = $shopifyService->getWishlistThemeEditorDeepLink(
                $store->shop,
                $target,
                $result['created']
            );

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

            $isAuthenticationIssue = str_contains($errorMsg, 'HTTP 401');

            $isPermissionIssue = str_contains($errorMsg, 'Access denied')
                || str_contains($errorMsg, '403')
                || str_contains(strtolower($errorMsg), 'permission');

            Log::warning('Wishlist setup failed', [
                'shop' => $store->shop,
                'target' => $target,
                'authentication_issue' => $isAuthenticationIssue,
                'error' => $errorMsg,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'created' => false,
                    'requires_reauth' => $isAuthenticationIssue,
                    'reauth_url' => $isAuthenticationIssue ? $reauthUrl : null,
                    'message' => $isAuthenticationIssue
                        ? 'Shopify authentication failed. Please reconnect your store.'
                        : ($isPermissionIssue
                            ? 'Shopify API permissions are insufficient. Please review app permissions.'
                            : 'Wishlist setup failed. Please try again.'),
                    'deep_link_url' => $deepLinkUrl,
                ], $isAuthenticationIssue ? 401 : 500);
            }

            if ($isAuthenticationIssue) {
                return redirect()->away($reauthUrl);
            }

            return redirect()->back()->with(
                'error',
                'Wishlist setup failed. Please try again.'
            );
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
