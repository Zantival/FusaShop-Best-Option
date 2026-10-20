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
        Schema::table('banner_requests', function (Blueprint $table) {
            $table->string('title')->nullable()->after('image_path');
            $table->string('target_type', 50)->default('store')->after('status'); // 'store' o 'products'
            $table->json('selected_products')->nullable()->after('target_type');
            $table->string('target_url', 1000)->nullable()->after('selected_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_requests', function (Blueprint $table) {
            $table->dropColumn(['title', 'target_type', 'selected_products', 'target_url']);
        });
    }
};
