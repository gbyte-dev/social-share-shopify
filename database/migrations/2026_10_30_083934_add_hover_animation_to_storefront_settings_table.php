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
            if (! Schema::hasColumn('storefront_settings', 'hover_animation')) {
                $table->string('hover_animation', 30)->default('lift')->after('icon_shape');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_settings', 'hover_animation')) {
                $table->dropColumn('hover_animation');
            }
        });
    }
};
