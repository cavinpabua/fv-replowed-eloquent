<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CraftingQueue extends Model
{
    protected $table = 'crafting_queue';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'uid',
        'recipe_id',
        'craft_type',
        'oven_slot',
        'start_ts',
        'finish_ts',
        'world_type',
        'status',
    ];

    protected $casts = [
        'oven_slot' => 'integer',
        'start_ts' => 'integer',
        'finish_ts' => 'integer',
    ];

    public static function getQueueForUser(string|int $uid, ?int $ovenSlot = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('uid', $uid)
            ->where('status', 'active');

        if ($ovenSlot !== null) {
            $query->where('oven_slot', $ovenSlot);
        }

        return $query->orderBy('start_ts')->get();
    }

    public static function getFinished(string|int $uid, ?string $recipeId = null, ?int $now = null): \Illuminate\Database\Eloquent\Collection
    {
        $now = $now ?? time();

        $query = static::where('uid', $uid)
            ->where('status', 'active')
            ->where('finish_ts', '<=', $now);

        if ($recipeId !== null) {
            $query->where('recipe_id', $recipeId);
        }

        return $query->get();
    }

    public static function addRecipe(
        string|int $uid,
        string $recipeId,
        string $craftType,
        int $ovenSlot,
        int $startTs,
        int $finishTs,
        string $worldType = 'farm'
    ): static {
        return static::create([
            'uid' => $uid,
            'recipe_id' => $recipeId,
            'craft_type' => $craftType,
            'oven_slot' => $ovenSlot,
            'start_ts' => $startTs,
            'finish_ts' => $finishTs,
            'world_type' => $worldType,
            'status' => 'active',
        ]);
    }

    public static function findActive(string|int $uid, string $recipeId, int $startTs): ?static
    {
        return static::where('uid', $uid)
            ->where('recipe_id', $recipeId)
            ->where('start_ts', $startTs)
            ->where('status', 'active')
            ->first();
    }

    public static function rushRecipe(string|int $uid, string $recipeId, int $startTs, int $newFinishTs): int
    {
        return static::where('uid', $uid)
            ->where('recipe_id', $recipeId)
            ->where('start_ts', $startTs)
            ->where('status', 'active')
            ->update(['finish_ts' => $newFinishTs]);
    }

    public static function deleteByRecipeId(string|int $uid, string $recipeId): int
    {
        return static::where('uid', $uid)
            ->where('recipe_id', $recipeId)
            ->delete();
    }

    public static function deleteAllForUser(string|int $uid, ?int $ovenSlot = null): int
    {
        $query = static::where('uid', $uid);

        if ($ovenSlot !== null) {
            $query->where('oven_slot', $ovenSlot);
        }

        return $query->delete();
    }

    public function complete(): bool
    {
        return $this->delete();
    }
}
