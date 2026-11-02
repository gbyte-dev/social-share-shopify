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
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('storefront_settings', 'product_share_enabled')) {
                $table->boolean('product_share_enabled')->default(true)->after('whatsapp_message');
            }
            if (! Schema::hasColumn('storefront_settings', 'product_share_button_text')) {
                $table->string('product_share_button_text', 50)->default('Share Product')->after('product_share_enabled');
            }
            if (! Schema::hasColumn('storefront_settings', 'product_share_button_style')) {
                $table->string('product_share_button_style', 30)->default('outline')->after('product_share_button_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('storefront_settings', 'product_share_button_style')) {
                $columns[] = 'product_share_button_style';
            }
            if (Schema::hasColumn('storefront_settings', 'product_share_button_text')) {
                $columns[] = 'product_share_button_text';
            }
            if (Schema::hasColumn('storefront_settings', 'product_share_enabled')) {
                $columns[] = 'product_share_enabled';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
