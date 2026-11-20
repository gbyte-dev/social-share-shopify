<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('shop_subscriptions', 'shopify_charge_id')) {
                $table->string('shopify_charge_id')->nullable()->index()->after('plan_id');
            }
            if (! Schema::hasColumn('shop_subscriptions', 'metadata')) {
                $table->json('metadata')->nullable()->after('current_period_end');
            }
        });

        // 1. Ensure Free plan exists and is active with price 0.00
        DB::table('plans')->updateOrInsert(
            ['slug' => 'free'],
            [
                'name' => 'Free Plan',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'permanent',
                'description' => 'Essential social sharing with up to 2 social links for growing Shopify merchants.',
                'features' => json_encode([
                    'Up to 2 Social Links',
                    'Storefront floating social icon dock',
                    'Basic click tracking analytics',
                    'Shopify Online Store 2.0 Theme Extension',
                    'Standard merchant support',
                ]),
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        // 2. Ensure Pro plan is active and accurately describes full entitlements
        DB::table('plans')->where('slug', 'pro')->update([
            'name' => 'SocialNest Pro',
            'price' => 5.00,
            'currency' => 'USD',
            'billing_interval' => '30_days',
            'description' => 'Complete storefront engagement suite with unlimited social links, custom styling, floating CTA, and full wishlist.',
            'features' => json_encode([
                'Unlimited Social Links',
                'Custom icon shapes & animation effects',
                'Storefront floating social icon dock',
                'WhatsApp direct product sharing',
                'Product Page Share Button with custom color picker',
                'Floating Call-to-Action widget',
                'Full Storefront Theme customization',
                'Full Wishlist feature suite',
                'Real-time click tracking & analytics',
                'Priority merchant support',
            ]),
            'is_active' => true,
            'updated_at' => now(),
        ]);

        $freePlan = DB::table('plans')->where('slug', 'free')->first();
        if ($freePlan) {
            // Find active installed stores without a currently valid subscription
            $stores = DB::table('shopify_stores')
                ->where('is_active', true)
                ->whereNotNull('installed_at')
                ->whereNull('uninstalled_at')
                ->get();

            foreach ($stores as $store) {
                $hasActiveSubscription = DB::table('shop_subscriptions')
                    ->where('shop_id', $store->id)
                    ->where('status', 'active')
                    ->where(function ($q): void {
                        $q->whereNull('current_period_end')
                            ->orWhere('current_period_end', '>', now());
                    })
                    ->exists();

                if (! $hasActiveSubscription) {
                    DB::table('shop_subscriptions')->insert([
                        'shop_id' => $store->id,
                        'plan_id' => $freePlan->id,
                        'status' => 'active',
                        'shopify_charge_id' => null,
                        'cancel_at_period_end' => false,
                        'cancelled_at' => null,
                        'activated_at' => now(),
                        'current_period_start' => now(),
                        'current_period_end' => null,
                        'metadata' => json_encode(['provisioned_reason' => 'initial_free_plan_restoration']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (empty($store->plan_name) || $store->plan_name === 'Free') {
                        DB::table('shopify_stores')->where('id', $store->id)->update([
                            'plan_name' => 'Free Plan',
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('shop_subscriptions', 'shopify_charge_id')) {
                $table->dropColumn('shopify_charge_id');
            }
            if (Schema::hasColumn('shop_subscriptions', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
