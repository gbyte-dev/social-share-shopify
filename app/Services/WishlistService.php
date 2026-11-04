<?php

namespace App\Services;

use App\Models\ShopifyStore;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WishlistService
{
    /**
     * Retrieve all wishlist items for a verified shop and identity.
     *
     * @return Collection<int, WishlistItem>
     */
    public function getItems(ShopifyStore $store, ?string $customerId, ?string $guestUuid): Collection
    {
        $this->validateIdentity($customerId, $guestUuid);

        return WishlistItem::forShop($store->id)
            ->forIdentity($customerId, $guestUuid)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Retrieve total wishlist item count for a verified shop and identity.
     */
    public function getCount(ShopifyStore $store, ?string $customerId, ?string $guestUuid): int
    {
        $this->validateIdentity($customerId, $guestUuid);

        return WishlistItem::forShop($store->id)
            ->forIdentity($customerId, $guestUuid)
            ->count();
    }

    /**
     * Determine if a given product ID is present in the customer or guest wishlist.
     */
    public function isInWishlist(ShopifyStore $store, ?string $customerId, ?string $guestUuid, string $productId): bool
    {
        $this->validateIdentity($customerId, $guestUuid);

        return WishlistItem::forShop($store->id)
            ->forIdentity($customerId, $guestUuid)
            ->where('product_id', (string) $productId)
            ->exists();
    }

    /**
     * Check multiple product IDs and return a status map [productId => bool].
     *
     * @param  list<string>  $productIds
     * @return array<string, bool>
     */
    public function getStatuses(ShopifyStore $store, ?string $customerId, ?string $guestUuid, array $productIds): array
    {
        $this->validateIdentity($customerId, $guestUuid);

        if (empty($productIds)) {
            return [];
        }

        $existing = WishlistItem::forShop($store->id)
            ->forIdentity($customerId, $guestUuid)
            ->whereIn('product_id', array_map('strval', $productIds))
            ->pluck('product_id')
            ->map(fn ($id) => (string) $id)
            ->flip()
            ->all();

        $result = [];
        foreach ($productIds as $pid) {
            $strPid = (string) $pid;
            $result[$strPid] = isset($existing[$strPid]);
        }

        return $result;
    }

    /**
     * Add a product to the customer or guest wishlist.
     * Preserves existing records without duplicating.
     *
     * @param  array<string, mixed>  $data
     * @return array{item: WishlistItem, created: bool, count: int}
     */
    public function addProduct(ShopifyStore $store, ?string $customerId, ?string $guestUuid, array $data): array
    {
        $this->validateIdentity($customerId, $guestUuid);

        $productId = (string) ($data['product_id'] ?? $data['id'] ?? '');
        if ($productId === '') {
            throw new InvalidArgumentException('product_id is required.');
        }

        $variantId = isset($data['variant_id']) && $data['variant_id'] !== '' ? (string) $data['variant_id'] : null;
        $title = isset($data['title']) ? (string) $data['title'] : null;
        $handle = isset($data['handle']) ? (string) $data['handle'] : null;
        $image = isset($data['image']) ? (string) $data['image'] : (isset($data['img']) ? (string) $data['img'] : null);
        $price = isset($data['price']) ? (string) $data['price'] : null;
        $url = isset($data['url']) ? (string) $data['url'] : null;
        $currency = isset($data['currency']) ? (string) $data['currency'] : null;

        $matchCriteria = [
            'shop_id' => $store->id,
            'product_id' => $productId,
        ];

        if ($customerId !== null && trim($customerId) !== '') {
            $matchCriteria['customer_id'] = $customerId;
            $matchCriteria['guest_uuid'] = null;
        } else {
            $matchCriteria['customer_id'] = null;
            $matchCriteria['guest_uuid'] = $guestUuid;
        }

        $created = false;
        $item = WishlistItem::where($matchCriteria)->first();

        if (! $item) {
            Log::info('[WishlistService] Inserting new wishlist item into database', [
                'shop_id' => $store->id,
                'product_id' => $productId,
                'is_customer' => $customerId !== null,
            ]);

            $item = WishlistItem::create(array_merge($matchCriteria, [
                'variant_id' => $variantId,
                'product_title' => $title,
                'product_handle' => $handle,
                'product_image' => $image,
                'product_price' => $price,
                'product_url' => $url,
                'currency' => $currency,
            ]));
            $created = true;
        } else {
            Log::info('[WishlistService] Wishlist item already exists in database', [
                'item_id' => $item->id,
                'product_id' => $productId,
            ]);
        }

        $count = $this->getCount($store, $customerId, $guestUuid);

        Log::info('[WishlistService] addProduct finished', [
            'item_id' => $item->id,
            'created' => $created,
            'total_count' => $count,
        ]);

        return [
            'item' => $item,
            'created' => $created,
            'count' => $count,
        ];
    }

    /**
     * Remove a product from the customer or guest wishlist.
     *
     * @return array{removed: bool, count: int}
     */
    public function removeProduct(ShopifyStore $store, ?string $customerId, ?string $guestUuid, string $productId): array
    {
        $this->validateIdentity($customerId, $guestUuid);

        $query = WishlistItem::forShop($store->id)
            ->forIdentity($customerId, $guestUuid)
            ->where(function ($q) use ($productId) {
                $q->where('product_id', (string) $productId)
                    ->orWhere('product_handle', (string) $productId);
            });

        $deleted = $query->delete();
        $count = $this->getCount($store, $customerId, $guestUuid);

        return [
            'removed' => $deleted > 0,
            'count' => $count,
        ];
    }

    /**
     * Merge a guest wishlist into an authenticated customer wishlist.
     * Uses atomic transaction, prevents duplicate products, deletes/invalidates
     * guest items, and guarantees store isolation.
     *
     * @return array{merged_count: int, total_count: int}
     */
    public function mergeGuestToCustomer(ShopifyStore $store, string $customerId, string $guestUuid): array
    {
        if (trim($customerId) === '' || trim($guestUuid) === '') {
            return [
                'merged_count' => 0,
                'total_count' => $this->getCount($store, $customerId, null),
            ];
        }

        return DB::transaction(function () use ($store, $customerId, $guestUuid): array {
            $guestItems = WishlistItem::where('shop_id', $store->id)
                ->where('guest_uuid', $guestUuid)
                ->get();

            $mergedCount = 0;

            foreach ($guestItems as $guestItem) {
                $alreadyExists = WishlistItem::where('shop_id', $store->id)
                    ->where('customer_id', $customerId)
                    ->where('product_id', $guestItem->product_id)
                    ->exists();

                if (! $alreadyExists) {
                    $guestItem->update([
                        'customer_id' => $customerId,
                        'guest_uuid' => null,
                    ]);
                    $mergedCount++;
                } else {
                    // Safe cleanup: delete the duplicate guest item
                    $guestItem->delete();
                }
            }

            $totalCount = WishlistItem::where('shop_id', $store->id)
                ->where('customer_id', $customerId)
                ->count();

            return [
                'merged_count' => $mergedCount,
                'total_count' => $totalCount,
            ];
        });
    }

    /**
     * Perform safe one-time migration of localStorage wishlist items into the database.
     * Inserts any item that does not already exist without throwing unique constraint errors.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{migrated_count: int, total_count: int}
     */
    public function migrateLocalItems(ShopifyStore $store, ?string $customerId, ?string $guestUuid, array $items): array
    {
        $this->validateIdentity($customerId, $guestUuid);

        return DB::transaction(function () use ($store, $customerId, $guestUuid, $items): array {
            $migratedCount = 0;

            foreach ($items as $rawItem) {
                if (! is_array($rawItem)) {
                    continue;
                }

                $productId = (string) ($rawItem['product_id'] ?? $rawItem['id'] ?? $rawItem['handle'] ?? '');
                if ($productId === '') {
                    continue;
                }

                $matchCriteria = [
                    'shop_id' => $store->id,
                    'product_id' => $productId,
                ];

                if ($customerId !== null && trim($customerId) !== '') {
                    $matchCriteria['customer_id'] = $customerId;
                    $matchCriteria['guest_uuid'] = null;
                } else {
                    $matchCriteria['customer_id'] = null;
                    $matchCriteria['guest_uuid'] = $guestUuid;
                }

                $exists = WishlistItem::where($matchCriteria)->exists();
                if (! $exists) {
                    WishlistItem::create(array_merge($matchCriteria, [
                        'variant_id' => isset($rawItem['variant_id']) ? (string) $rawItem['variant_id'] : null,
                        'product_title' => isset($rawItem['title']) ? (string) $rawItem['title'] : null,
                        'product_handle' => isset($rawItem['handle']) ? (string) $rawItem['handle'] : null,
                        'product_image' => isset($rawItem['img']) ? (string) $rawItem['img'] : (isset($rawItem['image']) ? (string) $rawItem['image'] : null),
                        'product_price' => isset($rawItem['price']) ? (string) $rawItem['price'] : null,
                        'product_url' => isset($rawItem['url']) ? (string) $rawItem['url'] : null,
                        'currency' => isset($rawItem['currency']) ? (string) $rawItem['currency'] : null,
                    ]));
                    $migratedCount++;
                }
            }

            $totalCount = $this->getCount($store, $customerId, $guestUuid);

            return [
                'migrated_count' => $migratedCount,
                'total_count' => $totalCount,
            ];
        });
    }

    /**
     * Validate that at least one identity is provided.
     */
    protected function validateIdentity(?string $customerId, ?string $guestUuid): void
    {
        $hasCustomer = $customerId !== null && trim($customerId) !== '';
        $hasGuest = $guestUuid !== null && trim($guestUuid) !== '';

        if (! $hasCustomer && ! $hasGuest) {
            throw new InvalidArgumentException('A customer_id or guest_uuid must be provided for wishlist identity.');
        }
    }
}
