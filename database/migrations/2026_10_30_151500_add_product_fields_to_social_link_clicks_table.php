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
        Schema::table('social_link_clicks', function (Blueprint $table) {
            if (! Schema::hasColumn('social_link_clicks', 'product_id')) {
                $table->string('product_id', 100)->nullable()->after('platform');
            }
            if (! Schema::hasColumn('social_link_clicks', 'product_title')) {
                $table->string('product_title', 255)->nullable()->after('product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_link_clicks', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('social_link_clicks', 'product_title')) {
                $columns[] = 'product_title';
            }
            if (Schema::hasColumn('social_link_clicks', 'product_id')) {
                $columns[] = 'product_id';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
