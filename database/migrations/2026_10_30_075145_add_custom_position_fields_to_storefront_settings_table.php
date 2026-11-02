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
            $table->string('custom_horizontal_align', 20)->default('right')->after('position');
            $table->string('custom_vertical_align', 20)->default('bottom')->after('custom_horizontal_align');
            $table->integer('custom_offset_x_desktop')->default(24)->after('custom_vertical_align');
            $table->integer('custom_offset_y_desktop')->default(24)->after('custom_offset_x_desktop');
            $table->integer('custom_offset_x_mobile')->nullable()->default(16)->after('custom_offset_y_desktop');
            $table->integer('custom_offset_y_mobile')->nullable()->default(16)->after('custom_offset_x_mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn([
                'custom_horizontal_align',
                'custom_vertical_align',
                'custom_offset_x_desktop',
                'custom_offset_y_desktop',
                'custom_offset_x_mobile',
                'custom_offset_y_mobile',
            ]);
        });
    }
};
