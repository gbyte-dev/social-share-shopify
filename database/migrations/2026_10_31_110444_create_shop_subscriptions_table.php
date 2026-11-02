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
        Schema::create('shop_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable()->index();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'current_period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_subscriptions');
    }
};
