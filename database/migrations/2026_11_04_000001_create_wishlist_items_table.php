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
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('customer_id', 64)->nullable()->index();
            $table->string('guest_uuid', 64)->nullable()->index();
            $table->string('product_id', 64)->index();
            $table->string('variant_id', 64)->nullable()->index();

            // Storefront product display metadata for fast instant rendering
            $table->string('product_handle')->nullable();
            $table->string('product_title')->nullable();
            $table->string('product_image', 1024)->nullable();
            $table->string('product_price', 64)->nullable();
            $table->string('product_url', 1024)->nullable();
            $table->string('currency', 16)->nullable();

            $table->timestamps();

            // Composite unique constraints to prevent duplicate products per shop & identity
            $table->unique(['shop_id', 'customer_id', 'product_id'], 'uniq_wishlist_customer_product');
            $table->unique(['shop_id', 'guest_uuid', 'product_id'], 'uniq_wishlist_guest_product');
        });

        // Database-level check constraint ensuring each record belongs to exactly one identity: customer_id OR guest_uuid
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE wishlist_items ADD CONSTRAINT chk_wishlist_single_identity CHECK ((customer_id IS NOT NULL AND guest_uuid IS NULL) OR (customer_id IS NULL AND guest_uuid IS NOT NULL))');
        } elseif ($driver === 'sqlite') {
            DB::statement("
                CREATE TRIGGER IF NOT EXISTS chk_wishlist_insert_identity BEFORE INSERT ON wishlist_items
                FOR EACH ROW
                WHEN NOT ((NEW.customer_id IS NOT NULL AND NEW.guest_uuid IS NULL) OR (NEW.customer_id IS NULL AND NEW.guest_uuid IS NOT NULL))
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: exactly one of customer_id or guest_uuid must be set');
                END;
            ");

            DB::statement("
                CREATE TRIGGER IF NOT EXISTS chk_wishlist_update_identity BEFORE UPDATE ON wishlist_items
                FOR EACH ROW
                WHEN NOT ((NEW.customer_id IS NOT NULL AND NEW.guest_uuid IS NULL) OR (NEW.customer_id IS NULL AND NEW.guest_uuid IS NOT NULL))
                BEGIN
                    SELECT RAISE(ABORT, 'CHECK constraint failed: exactly one of customer_id or guest_uuid must be set');
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS chk_wishlist_insert_identity');
            DB::statement('DROP TRIGGER IF EXISTS chk_wishlist_update_identity');
        }

        Schema::dropIfExists('wishlist_items');
    }
};
