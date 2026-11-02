<?php

namespace Database\Factories;

use App\Models\ShopifyStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShopifyStore>
 */
class ShopifyStoreFactory extends Factory
{
    protected $model = ShopifyStore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shopPrefix = Str::lower(fake()->unique()->slug(2));

        return [
            'shop' => "{$shopPrefix}.myshopify.com",
            'access_token' => 'shpat_'.Str::random(32),
            'scope' => 'read_products',
            'plan_name' => 'basic',
            'is_active' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ];
    }

    /**
     * Indicate that the store is uninstalled.
     */
    public function uninstalled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'uninstalled_at' => now(),
        ]);
    }
}
