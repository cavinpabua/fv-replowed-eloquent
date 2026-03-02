<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_stalls', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20);
            $table->integer('stall_object_id');
            $table->string('bushel_item_code', 10)->nullable();
            $table->tinyInteger('is_configured')->default(0);
            $table->bigInteger('date_closed')->default(0);
            $table->longText('inventory')->nullable();
            $table->timestamps();

            $table->index('uid');
            $table->unique(['uid', 'stall_object_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_stalls');
    }
};
