<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class WishlistStorefrontController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {}

    /**
     * Common CORS and security headers for storefront responses.
     *
     * @return array<string, string>
     */
    protected function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Guest-UUID, Authorization, Accept, X-Shopify-Shop-Domain',
            'Access-Control-Allow-Private-Network' => 'true',
            'Access-Control-Max-Age' => '86400',
        ];
    }

    /**
     * Get verified store from request attributes.
     */
    protected function getStore(Request $request): ShopifyStore
    {
        /** @var ShopifyStore $store */
        $store = $request->attributes->get('shopify_store');

        return $store;
    }

    /**
     * Get verified customer ID from request attributes.
     */
    protected function getCustomerId(Request $request): ?string
    {
        $customerId = $request->attributes->get('shopify_customer_id');

        return $customerId !== null && trim((string) $customerId) !== '' ? (string) $customerId : null;
    }

    /**
     * Extract and sanitize guest UUID from header, body, or query.
     */
    protected function getGuestUuid(Request $request): ?string
    {
        $raw = $request->header('X-Guest-UUID')
            ?? $request->input('guest_uuid')
            ?? $request->query('guest_uuid');

        if (! is_string($raw)) {
            return null;
        }

        $clean = trim($raw);

        // Standard UUID or alphanumeric identifier between 16 and 64 characters
        if (preg_match('/^[a-zA-Z0-9\-_]{16,64}$/', $clean)) {
            return $clean;
        }

        return null;
    }

    /**
     * Fetch all wishlist items and count for current identity.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);
        $guestUuid = $this->getGuestUuid($request);

        Log::info('[WishlistController] index requested', [
            'shop' => $store->shop,
            'has_guest_uuid' => ! empty($guestUuid),
            'is_customer' => $customerId !== null,
        ]);

        if (! $customerId && ! $guestUuid) {
            Log::warning('[WishlistController] index missing identity');

            return response()->json([
                'success' => false,
                'error' => 'A guest_uuid or customer session is required to retrieve wishlist items.',
                'code' => 'MISSING_IDENTITY',
            ], 422, $this->corsHeaders());
        }

        // Automatic guest-to-customer migration when authenticated customer has a prior guest session
        if ($customerId && $guestUuid) {
            $this->wishlistService->mergeGuestToCustomer($store, $customerId, $guestUuid);
        }

        $items = $this->wishlistService->getItems($store, $customerId, $guestUuid);

        Log::info('[WishlistController] index returning items', [
            'shop' => $store->shop,
            'count' => $items->count(),
        ]);

        return response()->json([
            'success' => true,
            'items' => $items->map(fn ($item) => $item->toStorefrontArray())->values(),
            'count' => $items->count(),
            'is_customer' => $customerId !== null,
            'customer_id' => $customerId ? substr($customerId, 0, 4).'****' : null,
        ], 200, $this->corsHeaders());
    }

    /**
     * Get item count only for header badge rendering.
     */
    public function count(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);
        $guestUuid = $this->getGuestUuid($request);

        if (! $customerId && ! $guestUuid) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'is_customer' => false,
            ], 200, $this->corsHeaders());
        }

        $count = $this->wishlistService->getCount($store, $customerId, $guestUuid);

        return response()->json([
            'success' => true,
            'count' => $count,
            'is_customer' => $customerId !== null,
        ], 200, $this->corsHeaders());
    }

    /**
     * Check if one or more products are in the wishlist.
     */
    public function status(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);
        $guestUuid = $this->getGuestUuid($request);

        if (! $customerId && ! $guestUuid) {
            return response()->json([
                'success' => true,
                'in_wishlist' => false,
                'statuses' => [],
                'count' => 0,
            ], 200, $this->corsHeaders());
        }

        $productId = $request->query('product_id') ?? $request->query('id');
        $productIdsParam = $request->query('product_ids');

        if ($productIdsParam) {
            $ids = is_array($productIdsParam)
                ? $productIdsParam
                : explode(',', (string) $productIdsParam);

            $ids = array_filter(array_map('trim', $ids));
            $statuses = $this->wishlistService->getStatuses($store, $customerId, $guestUuid, $ids);

            return response()->json([
                'success' => true,
                'statuses' => $statuses,
                'count' => $this->wishlistService->getCount($store, $customerId, $guestUuid),
            ], 200, $this->corsHeaders());
        }

        if (! $productId || ! is_string($productId)) {
            return response()->json([
                'success' => false,
                'error' => 'product_id parameter is required.',
                'code' => 'INVALID_INPUT',
            ], 422, $this->corsHeaders());
        }

        $inWishlist = $this->wishlistService->isInWishlist($store, $customerId, $guestUuid, $productId);
        $count = $this->wishlistService->getCount($store, $customerId, $guestUuid);

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
            'product_id' => (string) $productId,
            'count' => $count,
        ], 200, $this->corsHeaders());
    }

    /**
     * Add a product to the wishlist.
     */
    public function add(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);
        $guestUuid = $this->getGuestUuid($request);

        if (! $customerId && ! $guestUuid) {
            return response()->json([
                'success' => false,
                'error' => 'A guest_uuid or customer session is required to add items to wishlist.',
                'code' => 'MISSING_IDENTITY',
            ], 422, $this->corsHeaders());
        }

        $validator = Validator::make($request->all(), [
            'product_id' => ['required_without:id', 'string', 'max:64'],
            'id' => ['nullable', 'string', 'max:64'],
            'variant_id' => ['nullable', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:1024'],
            'img' => ['nullable', 'string', 'max:1024'],
            'price' => ['nullable', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:1024'],
            'currency' => ['nullable', 'string', 'max:16'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422, $this->corsHeaders());
        }

        $data = $validator->validated();

        Log::info('[WishlistController] Processing add request', [
            'shop' => $store->shop,
            'product_id' => $data['product_id'] ?? $data['id'] ?? null,
            'has_guest_uuid' => ! empty($guestUuid),
            'is_customer' => $customerId !== null,
        ]);

        try {
            $result = $this->wishlistService->addProduct($store, $customerId, $guestUuid, $data);

            Log::info('[WishlistController] Product added successfully', [
                'shop' => $store->shop,
                'product_id' => $data['product_id'] ?? $data['id'] ?? null,
                'created' => $result['created'],
                'count' => $result['count'],
            ]);

            return response()->json([
                'success' => true,
                'item' => $result['item']->toStorefrontArray(),
                'created' => $result['created'],
                'count' => $result['count'],
            ], 200, $this->corsHeaders());
        } catch (InvalidArgumentException $e) {
            Log::warning('[WishlistController] addProduct invalid argument', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'INVALID_ARGUMENT',
            ], 422, $this->corsHeaders());
        }
    }

    /**
     * Remove a product from the wishlist.
     */
    public function remove(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);
        $guestUuid = $this->getGuestUuid($request);

        if (! $customerId && ! $guestUuid) {
            return response()->json([
                'success' => false,
                'error' => 'A guest_uuid or customer session is required to remove items from wishlist.',
                'code' => 'MISSING_IDENTITY',
            ], 422, $this->corsHeaders());
        }

        $productId = $request->input('product_id')
            ?? $request->input('id')
            ?? $request->query('product_id')
            ?? $request->query('id');

        if (! $productId || ! is_string($productId)) {
            return response()->json([
                'success' => false,
                'error' => 'product_id is required to remove item.',
                'code' => 'INVALID_INPUT',
            ], 422, $this->corsHeaders());
        }

        $result = $this->wishlistService->removeProduct($store, $customerId, $guestUuid, (string) $productId);

        return response()->json([
            'success' => true,
            'removed' => $result['removed'],
            'count' => $result['count'],
        ], 200, $this->corsHeaders());
    }

    /**
     * Merge guest wishlist into authenticated customer wishlist after customer login.
     */
    public function merge(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);

        if (! $customerId) {
            return response()->json([
                'success' => false,
                'error' => 'Customer must be authenticated via Shopify to merge guest wishlist.',
                'code' => 'UNAUTHENTICATED_CUSTOMER',
            ], 403, $this->corsHeaders());
        }

        $guestUuid = $this->getGuestUuid($request);
        if (! $guestUuid) {
            return response()->json([
                'success' => false,
                'error' => 'guest_uuid is required to perform merge.',
                'code' => 'MISSING_GUEST_UUID',
            ], 422, $this->corsHeaders());
        }

        $result = $this->wishlistService->mergeGuestToCustomer($store, $customerId, $guestUuid);
        $items = $this->wishlistService->getItems($store, $customerId, null);

        return response()->json([
            'success' => true,
            'merged_count' => $result['merged_count'],
            'total_count' => $result['total_count'],
            'items' => $items->map(fn ($item) => $item->toStorefrontArray())->values(),
        ], 200, $this->corsHeaders());
    }

    /**
     * Migrate existing local storage products safely to the database.
     */
    public function migrateLocal(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $customerId = $this->getCustomerId($request);
        $guestUuid = $this->getGuestUuid($request);

        if (! $customerId && ! $guestUuid) {
            return response()->json([
                'success' => false,
                'error' => 'A guest_uuid or customer session is required for local storage migration.',
                'code' => 'MISSING_IDENTITY',
            ], 422, $this->corsHeaders());
        }

        $rawItems = $request->input('items');
        if (! is_array($rawItems)) {
            return response()->json([
                'success' => false,
                'error' => 'items array is required for migration.',
                'code' => 'INVALID_PAYLOAD',
            ], 422, $this->corsHeaders());
        }

        // Limit migration batch to 100 items per request to prevent abuse
        $items = array_slice($rawItems, 0, 100);
        $result = $this->wishlistService->migrateLocalItems($store, $customerId, $guestUuid, $items);
        $allItems = $this->wishlistService->getItems($store, $customerId, $guestUuid);

        return response()->json([
            'success' => true,
            'migrated_count' => $result['migrated_count'],
            'total_count' => $result['total_count'],
            'items' => $allItems->map(fn ($item) => $item->toStorefrontArray())->values(),
        ], 200, $this->corsHeaders());
    }
}
