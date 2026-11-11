<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->string('custom_icon', 50)->nullable()->after('label');
            $table->string('custom_icon_color', 20)->nullable()->after('custom_icon');
        });
    }

    public function down(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->dropColumn(['custom_icon', 'custom_icon_color']);
        });
    }
};