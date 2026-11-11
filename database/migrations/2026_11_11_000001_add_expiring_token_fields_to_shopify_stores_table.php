
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->dropColumn([
                'refresh_token',
                'access_token_expires_at',
                'refresh_token_expires_at',
            ]);
        });
    }
};
