<?php

namespace Database\Factories;

use App\Models\ShopifyStore;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => ShopifyStore::factory(),
            'customer_id' => null,
            'guest_uuid' => (string) Str::uuid(),
            'product_id' => (string) fake()->numberBetween(1000000000, 9999999999),
            'variant_id' => (string) fake()->numberBetween(1000000000, 9999999999),
            'product_handle' => fake()->slug(),
            'product_title' => fake()->words(3, true),
            'product_image' => 'https://cdn.shopify.com/s/files/1/0000/0000/products/sample.jpg',
            'product_price' => '$'.fake()->randomFloat(2, 5, 200),
            'product_url' => '/products/'.fake()->slug(),
            'currency' => 'USD',
        ];
    }

    /**
     * State for a customer item.
     */
    public function forCustomer(string $customerId): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
            'guest_uuid' => null,
        ]);
    }

    /**
     * State for a guest item.
     */
    public function forGuest(string $guestUuid): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
            'guest_uuid' => $guestUuid,
        ]);
    }
}
