<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds unique constraints on uid columns to prevent duplicate user IDs.
     * The users table uid must be unique as it's the primary identifier across tables.
     * usermeta and useravatars also get unique constraints as they have one row per user.
     */
    public function up(): void
    {
        // Add unique constraint to users.uid
        Schema::table('users', function (Blueprint $table) {
            $table->unique('uid');
        });

        // Add unique constraint to usermeta.uid (one meta row per user)
        Schema::table('usermeta', function (Blueprint $table) {
            $table->unique('uid');
        });

        // Add unique constraint to useravatars.uid (one avatar row per user)
        Schema::table('useravatars', function (Blueprint $table) {
            $table->unique('uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['uid']);
        });

        Schema::table('usermeta', function (Blueprint $table) {
            $table->dropUnique(['uid']);
        });

        Schema::table('useravatars', function (Blueprint $table) {
            $table->dropUnique(['uid']);
        });
    }
};
