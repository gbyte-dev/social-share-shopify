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
        Schema::create('shopify_stores', function (Blueprint $table) {
            $table->id();
            $table->string('shop')->unique()->index();
            $table->text('access_token');
            $table->text('scope')->nullable();
            $table->string('plan_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_stores');
    }
};
