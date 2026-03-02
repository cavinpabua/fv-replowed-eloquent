<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CraftingRecipeState extends Model
{
    protected $table = 'crafting_recipe_states';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'recipe_id',
        'xp',
        'times_crafted',
    ];

    protected $casts = [
        'xp' => 'integer',
        'times_crafted' => 'integer',
    ];

    public static function getState(string|int $uid, string $recipeId): ?static
    {
        return static::where('uid', $uid)
            ->where('recipe_id', $recipeId)
            ->first();
    }

    public static function getAllForUser(string|int $uid): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('uid', $uid)->get();
    }

    public static function addXp(string|int $uid, string $recipeId, int $xp): static
    {
        return static::updateOrCreate(
            ['uid' => $uid, 'recipe_id' => $recipeId],
            ['xp' => \DB::raw("COALESCE(xp, 0) + {$xp}")]
        );
    }

    public static function incrementCrafted(string|int $uid, string $recipeId): static
    {
        return static::updateOrCreate(
            ['uid' => $uid, 'recipe_id' => $recipeId],
            ['times_crafted' => \DB::raw("COALESCE(times_crafted, 0) + 1")]
        );
    }
}
