<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Products
        if (!Schema::hasColumn('products', 'available_options')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('available_options')->nullable()->after('image');
                $table->json('images')->nullable()->after('available_options');
            });
        }

        // Cart Items
        if (!Schema::hasColumn('cart_items', 'selected_options')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->json('selected_options')->nullable()->after('quantity');
            });
        }

        // Try to drop unique index on cart_items
        try {
            // Using raw SQL for MySQL to avoid Blueprint issues with existing columns
            DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_cart_id_product_id_unique');
        } catch (\Exception $e) {
            // Index might already be dropped or named differently
        }

        // Order Items
        if (!Schema::hasColumn('order_items', 'selected_options')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->json('selected_options')->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['available_options', 'images']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('selected_options');
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('selected_options');
        });
    }
};
