<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $col) {
            $col->json('specifications')->nullable()->after('available_options');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $col) {
            $col->dropColumn('specifications');
        });
    }
};
