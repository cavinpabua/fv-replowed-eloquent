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
        Schema::create('daily_gifts', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->index();
            $table->integer('cash_amount');
            $table->integer('gold_amount');
            $table->date('claimed_at');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Ensure one claim per user per day
            $table->unique(['uid', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_gifts');
    }
};
