<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_sets', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20);
            $table->string('code', 10);
            $table->integer('fs_index');
            $table->text('friends')->nullable();
            $table->text('pending')->nullable();
            $table->integer('bought_count')->default(0);
            $table->tinyInteger('progress_state')->default(0);
            $table->bigInteger('start_time')->default(0);
            $table->string('world_code', 50)->nullable();
            $table->string('reward_link', 255)->default('');
            $table->timestamps();

            $table->index(['uid', 'code']);
            $table->unique(['uid', 'code', 'fs_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_sets');
    }
};
