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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('category', 50)->nullable()->index();
            $table->integer('priority')->default(1);
            $table->boolean('replay')->default(false);
            $table->boolean('skip')->default(false);
            $table->boolean('kill_quest')->default(false);
            $table->integer('mem_store_id')->nullable()->index();
            $table->json('prereqs')->nullable();
            $table->json('children')->nullable();
            $table->json('tasks')->nullable();
            $table->json('rewards')->nullable();
            $table->json('frontend')->nullable();
            $table->json('friend_reward')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
