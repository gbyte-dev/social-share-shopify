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
        Schema::create('global_custom_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('website_url', 1000)->nullable();
            $table->text('logo_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_custom_platforms');
    }
};