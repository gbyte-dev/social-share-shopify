<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            if (! Schema::hasColumn('shopify_stores', 'reauthorization_required')) {
                $table->boolean('reauthorization_required')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            if (Schema::hasColumn('shopify_stores', 'reauthorization_required')) {
                $table->dropColumn('reauthorization_required');
            }
        });
    }
};
