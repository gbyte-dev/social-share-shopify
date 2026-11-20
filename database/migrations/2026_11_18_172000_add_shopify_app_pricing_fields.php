<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table): void {
            if (! Schema::hasColumn('shopify_stores', 'shopify_shop_gid')) {
                $table->string('shopify_shop_gid')->nullable()->index()->after('shop');
            }
        });

        Schema::table('shop_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('shop_subscriptions', 'shopify_app_gid')) {
                $table->string('shopify_app_gid')->nullable()->after('shopify_charge_id');
            }
            if (! Schema::hasColumn('shop_subscriptions', 'plan_handle')) {
                $table->string('plan_handle')->nullable()->index()->after('shopify_app_gid');
            }
            if (! Schema::hasColumn('shop_subscriptions', 'last_verified_at')) {
                $table->timestamp('last_verified_at')->nullable()->after('activated_at');
            }
            if (! Schema::hasColumn('shop_subscriptions', 'billing_interval')) {
                $table->string('billing_interval')->nullable()->after('last_verified_at');
            }
            if (! Schema::hasColumn('shop_subscriptions', 'response_snapshot')) {
                $table->json('response_snapshot')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table): void {
            if (Schema::hasColumn('shopify_stores', 'shopify_shop_gid')) {
                $table->dropColumn('shopify_shop_gid');
            }
        });

        Schema::table('shop_subscriptions', function (Blueprint $table): void {
            $columnsToDrop = array_filter([
                Schema::hasColumn('shop_subscriptions', 'shopify_app_gid') ? 'shopify_app_gid' : null,
                Schema::hasColumn('shop_subscriptions', 'plan_handle') ? 'plan_handle' : null,
                Schema::hasColumn('shop_subscriptions', 'last_verified_at') ? 'last_verified_at' : null,
                Schema::hasColumn('shop_subscriptions', 'billing_interval') ? 'billing_interval' : null,
                Schema::hasColumn('shop_subscriptions', 'response_snapshot') ? 'response_snapshot' : null,
            ]);

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
