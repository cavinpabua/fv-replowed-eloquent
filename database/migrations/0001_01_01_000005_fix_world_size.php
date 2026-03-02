<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE userworlds SET sizeX = 50, sizeY = 50 WHERE sizeX = 48 AND sizeY = 48");
        // Clear farm-specific starter plots from non-farm worlds
        $empty = serialize(array());
        DB::statement("UPDATE userworlds SET objects = ? WHERE type != 'farm'", [$empty]);
    }

    public function down(): void
    {
        DB::statement("UPDATE userworlds SET sizeX = 48, sizeY = 48 WHERE sizeX = 50 AND sizeY = 50");
    }
};
