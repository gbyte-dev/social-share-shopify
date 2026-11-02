<?php

namespace Database\Factories;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
{
    protected $model = SocialLink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopify_store_id' => ShopifyStore::factory(),
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/myboutique',
            'label' => 'Follow on Instagram',
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
