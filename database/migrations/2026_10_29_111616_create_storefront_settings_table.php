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
        Schema::create('storefront_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->cascadeOnDelete()->unique();
            $table->boolean('is_enabled')->default(true);
            $table->string('position', 50)->default('bottom_right');
            $table->string('icon_size', 20)->default('medium');
            $table->string('icon_shape', 20)->default('circle');
            $table->text('whatsapp_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_settings');
    }
};
