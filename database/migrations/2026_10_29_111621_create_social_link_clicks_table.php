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
        Schema::create('social_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->foreignId('social_link_id')->nullable()->constrained('social_links')->nullOnDelete();
            $table->string('platform', 50);
            $table->string('referrer', 500)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['shopify_store_id', 'created_at']);
            $table->index(['shopify_store_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_link_clicks');
    }
};
