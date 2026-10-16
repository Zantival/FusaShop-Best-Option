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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('points', 12, 2)->default(0)->after('is_blocked');
            $table->timestamp('accepted_terms_at')->nullable()->after('points');
            $table->json('notification_preferences')->nullable()->after('accepted_terms_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['points', 'accepted_terms_at', 'notification_preferences']);
        });
    }
};
