<?php

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
        Schema::table('social_link_clicks', function (Blueprint $table) {
            if (! Schema::hasColumn('social_link_clicks', 'link_type')) {
                $table->string('link_type', 50)->default('floating_dock')->after('platform');
            }
            if (! Schema::hasColumn('social_link_clicks', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('product_title');
            }

            $table->index(['shopify_store_id', 'link_type'], 'social_link_clicks_store_link_type_index');
            $table->index(['shopify_store_id', 'clicked_at'], 'social_link_clicks_store_clicked_at_index');
            $table->index(['shopify_store_id', 'social_link_id'], 'social_link_clicks_store_social_link_id_index');
        });

        DB::table('social_link_clicks')
            ->whereNull('clicked_at')
            ->update([
                'clicked_at' => DB::raw('created_at'),
            ]);

        DB::table('social_link_clicks')
            ->where(function ($query) {
                $query->whereNotNull('product_id')
                    ->orWhereIn('platform', ['copy_link', 'native']);
            })
            ->update([
                'link_type' => 'product_share',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_link_clicks', function (Blueprint $table) {
            $table->dropIndex('social_link_clicks_store_link_type_index');
            $table->dropIndex('social_link_clicks_store_clicked_at_index');
            $table->dropIndex('social_link_clicks_store_social_link_id_index');

            $columns = [];
            if (Schema::hasColumn('social_link_clicks', 'clicked_at')) {
                $columns[] = 'clicked_at';
            }
            if (Schema::hasColumn('social_link_clicks', 'link_type')) {
                $columns[] = 'link_type';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
