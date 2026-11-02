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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 8, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval')->default('30_days');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed existing plans so they are immediately available
        DB::table('plans')->insert([
            [
                'id' => 1,
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'description' => 'Essential social sharing for growing Shopify merchants getting started with customer engagement.',
                'features' => json_encode([
                    'Up to 7 supported social platforms',
                    'Storefront floating social icon dock',
                    'WhatsApp direct product sharing',
                    'Shopify Online Store 2.0 Theme Extension',
                    'Basic click tracking analytics',
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 4.99,
                'currency' => 'USD',
                'billing_interval' => '30_days',
                'description' => 'Advanced storefront customization and deep shopper analytics for established high-volume brands.',
                'features' => json_encode([
                    'Everything in the Free plan',
                    'Custom icon shapes & animation effects',
                    'Advanced UTM parameter tagging',
                    'Page-specific widget visibility rules',
                    'Priority developer support',
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
