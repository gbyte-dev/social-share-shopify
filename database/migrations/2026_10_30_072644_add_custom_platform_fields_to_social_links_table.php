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
        Schema::table('social_links', function (Blueprint $table) {
            // Drop legacy unique constraint that prevented multiple custom platforms per store
            try {
                $table->dropUnique(['shopify_store_id', 'platform']);
            } catch (Throwable) {
                // Safe ignore if index does not exist or has already been dropped
            }

            $table->index(['shopify_store_id', 'platform']);

            // Custom platform fields
            $table->string('platform_type', 20)->default('builtin')->after('platform')->index();
            $table->string('custom_platform_name', 100)->nullable()->after('platform_type');
            $table->text('official_website_url')->nullable()->after('custom_platform_name');
            $table->string('original_logo_path')->nullable()->after('official_website_url');
            $table->string('logo_small_path')->nullable()->after('original_logo_path');
            $table->string('logo_medium_path')->nullable()->after('logo_small_path');
            $table->string('logo_large_path')->nullable()->after('logo_medium_path');
            $table->text('logo_source_url')->nullable()->after('logo_large_path');
            $table->string('logo_source_domain', 150)->nullable()->after('logo_source_url');
            $table->string('alt_text', 150)->nullable()->after('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->dropColumn([
                'platform_type',
                'custom_platform_name',
                'official_website_url',
                'original_logo_path',
                'logo_small_path',
                'logo_medium_path',
                'logo_large_path',
                'logo_source_url',
                'logo_source_domain',
                'alt_text',
            ]);

            try {
                $table->unique(['shopify_store_id', 'platform']);
            } catch (Throwable) {
                // Safe fallback
            }
        });
    }
};
