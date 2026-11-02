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
            if (! Schema::hasColumn('storefront_settings', 'product_share_button_background_color')) {
                $table->string('product_share_button_background_color', 50)
                    ->default('rgba(17, 24, 39, 1)')
                    ->after('product_share_button_style');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_settings', 'product_share_button_background_color')) {
                $table->dropColumn('product_share_button_background_color');
            }
        });
    }
};
