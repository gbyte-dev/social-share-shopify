<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $features = json_encode([
            'Core floating social sharing dock',
            'WhatsApp product sharing',
            'Standard dock positioning',
            'Shopify Theme App Extension',
            'Add Wishlist feature on store',
            'All features free for now',
            'Unlimited Social Links',
            'Floating Social Dock',
            'Storefront Customization',
            'Product Sharing',
            'Wishlist',
            'Analytics & Click Tracking',
            'Complete Dashboard Access',
            'All Available SocialNest Features',
        ]);

        // Fix existing corrupted row 3 if slug has a space
        DB::table('plans')
            ->where('slug', 'socialvire starter')
            ->update([
                'slug' => 'starter',
                'name' => 'Socialvire Starter',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'social_link_limit' => null,
                'description' => "Everything you need to connect your storefront with your customers' social platforms.",
                'features' => $features,
                'is_active' => true,
                'updated_at' => now(),
            ]);

        // 1. Ensure 'starter' exists
        DB::table('plans')->updateOrInsert(
            ['slug' => 'starter'],
            [
                'name' => 'Socialvire Starter',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'social_link_limit' => null,
                'description' => "Everything you need to connect your storefront with your customers' social platforms.",
                'features' => $features,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        // 2. Ensure 'socialvire-starter' alias exists
        DB::table('plans')->updateOrInsert(
            ['slug' => 'socialvire-starter'],
            [
                'name' => 'Socialvire Starter',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'social_link_limit' => null,
                'description' => "Everything you need to connect your storefront with your customers' social platforms.",
                'features' => $features,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        // 3. Ensure 'free' exists
        DB::table('plans')->updateOrInsert(
            ['slug' => 'free'],
            [
                'name' => 'Socialvire Free',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'social_link_limit' => null,
                'description' => "Everything you need to connect your storefront with your customers' social platforms.",
                'features' => $features,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        // 4. Ensure 'pro' exists
        DB::table('plans')->updateOrInsert(
            ['slug' => 'pro'],
            [
                'name' => 'Socialvire Pro',
                'price' => 5.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'social_link_limit' => null,
                'description' => 'Complete storefront engagement suite with unlimited links, custom branding, and priority support.',
                'features' => $features,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to preserve plan records
    }
};
