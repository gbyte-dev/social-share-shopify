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
        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'social_link_limit')) {
                $table->unsignedInteger('social_link_limit')->nullable()->after('billing_interval');
            }
        });

        // Set Free plan to 2, Pro plan to null (unlimited) or configured limit
        DB::table('plans')->where('slug', 'free')->update([
            'social_link_limit' => 2,
        ]);

        DB::table('plans')->where('slug', 'pro')->update([
            'social_link_limit' => null, // null represents unlimited in plan schema
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            if (Schema::hasColumn('plans', 'social_link_limit')) {
                $table->dropColumn('social_link_limit');
            }
        });
    }
};
