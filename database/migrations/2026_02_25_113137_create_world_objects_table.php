<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_objects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('world_id');

            // Core fields (all objects)
            $table->integer('object_id');
            $table->string('class_name', 50);
            $table->string('item_name', 100)->nullable();
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->integer('position_z')->default(0);
            $table->tinyInteger('direction')->default(0);
            $table->string('state', 50)->nullable();
            $table->boolean('deleted')->default(false);
            $table->integer('temp_id')->default(-1);
            $table->string('instance_data_store_key', 100)->nullable();
            $table->json('components')->nullable();

            // Timing fields
            $table->bigInteger('plant_time')->default(0);
            $table->bigInteger('build_time')->default(0);

            // Plot-specific
            $table->boolean('is_big_plot')->default(false);
            $table->boolean('is_jumbo')->default(false);
            $table->boolean('is_produce_item')->default(false);

            // FeatureBuilding / CraftingCottageBuilding
            $table->json('contents')->nullable();
            $table->integer('expansion_level')->default(1);
            $table->json('expansion_parts')->nullable();

            // Equipment
            $table->integer('equipment_parts_count')->default(0);

            // MessageSign
            $table->text('message')->nullable();
            $table->integer('message_id')->nullable();
            $table->string('author_id', 20)->nullable();
            $table->string('host_id', 20)->nullable();
            $table->double('message_timestamp')->nullable();

            $table->timestamps();

            // Indexes
            $table->foreign('world_id')->references('id')->on('userworlds')->onDelete('cascade');
            $table->index('world_id');
            $table->index('class_name');
            $table->index('item_name');
            $table->index(['world_id', 'position_x', 'position_y'], 'idx_world_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_objects');
    }
};
