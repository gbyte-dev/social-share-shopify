<?php

use Carbon\Carbon;
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
        Schema::table('shop_subscriptions', function (Blueprint $table) {
            $table->boolean('cancel_at_period_end')->default(false)->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_at_period_end');
        });

        // Ensure Pro plan is named 'SocialNest Pro' at $5.00 / 30 days
        DB::table('plans')->where('slug', 'pro')->update([
            'name' => 'SocialNest Pro',
            'price' => 5.00,
            'currency' => 'USD',
            'billing_interval' => '30_days',
            'description' => 'Complete storefront engagement suite with floating social docks, WhatsApp product sharing, and real-time analytics.',
            'features' => json_encode([
                'All 7+ supported social platforms',
                'Custom icon shapes & animation effects',
                'Storefront floating social icon dock',
                'WhatsApp direct product sharing',
                'Product Page Share Button with custom color picker',
                'Floating Call-to-Action widget',
                'Real-time click tracking & analytics',
                'Priority merchant support',
            ]),
            'is_active' => true,
            'updated_at' => now(),
        ]);

        // Deactivate Free plan so it cannot be selected or renewed
        DB::table('plans')->where('slug', 'free')->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        // Safely expire any existing Free subscriptions while preserving history
        $freePlanId = DB::table('plans')->where('slug', 'free')->value('id');
        if ($freePlanId) {
            DB::table('shop_subscriptions')
                ->where('plan_id', $freePlanId)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'updated_at' => now(),
                ]);
        }

        // Expire any subscription with anomalous duration (> 30 days) to prevent 60-day leaks
        $subscriptions = DB::table('shop_subscriptions')->where('status', 'active')->get();
        foreach ($subscriptions as $sub) {
            if ($sub->current_period_start && $sub->current_period_end) {
                $start = Carbon::parse($sub->current_period_start);
                $end = Carbon::parse($sub->current_period_end);
                if ($start->diffInDays($end) > 30) {
                    DB::table('shop_subscriptions')->where('id', $sub->id)->update([
                        'current_period_end' => $start->copy()->addDays(30),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['cancel_at_period_end', 'cancelled_at']);
        });

        DB::table('plans')->where('slug', 'free')->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        DB::table('plans')->where('slug', 'pro')->update([
            'name' => 'Pro',
            'price' => 4.99,
            'updated_at' => now(),
        ]);
    }
};
