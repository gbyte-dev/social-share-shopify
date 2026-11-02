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
        Schema::table('storefront_settings', function (Blueprint $table): void {
            $table->boolean('floating_cta_enabled')->default(false)->after('product_share_button_background_color');
            $table->string('floating_cta_app_name', 100)->nullable()->after('floating_cta_enabled');
            $table->string('floating_cta_button_text', 100)->default('Get Started')->after('floating_cta_app_name');
            $table->text('floating_cta_destination_url')->nullable()->after('floating_cta_button_text');
            $table->string('floating_cta_icon_path', 500)->nullable()->after('floating_cta_destination_url');
            $table->boolean('floating_cta_show_icon')->default(true)->after('floating_cta_icon_path');
            $table->string('floating_cta_background_color', 50)->default('rgba(17, 24, 39, 1)')->after('floating_cta_show_icon');
            $table->string('floating_cta_text_color', 50)->default('rgba(255, 255, 255, 1)')->after('floating_cta_background_color');
            $table->string('floating_cta_button_size', 20)->default('medium')->after('floating_cta_text_color');
            $table->boolean('floating_cta_desktop_enabled')->default(true)->after('floating_cta_button_size');
            $table->boolean('floating_cta_mobile_enabled')->default(true)->after('floating_cta_desktop_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'floating_cta_enabled',
                'floating_cta_app_name',
                'floating_cta_button_text',
                'floating_cta_destination_url',
                'floating_cta_icon_path',
                'floating_cta_show_icon',
                'floating_cta_background_color',
                'floating_cta_text_color',
                'floating_cta_button_size',
                'floating_cta_desktop_enabled',
                'floating_cta_mobile_enabled',
            ]);
        });
    }
};
