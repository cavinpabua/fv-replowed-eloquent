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
        Schema::create('avatar_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid')->index();
            $table->string('item_id', 20)->index();
            $table->timestamp('purchased_at')->useCurrent();

            $table->unique(['uid', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avatar_unlocks');
    }
};
