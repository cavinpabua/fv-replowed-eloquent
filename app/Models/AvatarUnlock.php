<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvatarUnlock extends Model
{
    protected $table = 'avatar_unlocks';

    public $timestamps = false;

    const CREATED_AT = 'purchased_at';

    protected $fillable = [
        'uid',
        'item_id',
    ];

    public static function getUnlockedForUser(string|int $uid): \stdClass
    {
        $items = static::where('uid', $uid)->pluck('item_id');

        $unlocked = new \stdClass();
        foreach ($items as $itemId) {
            $key = (string) $itemId;
            $unlocked->$key = true;
        }
        return $unlocked;
    }

    public static function getUnlockedArray(string|int $uid): array
    {
        return static::where('uid', $uid)->pluck('item_id')->toArray();
    }

    public static function isUnlocked(string|int $uid, string $itemId): bool
    {
        return static::where('uid', $uid)
            ->where('item_id', $itemId)
            ->exists();
    }

    public static function unlock(string|int $uid, string $itemId): bool
    {
        return static::firstOrCreate([
            'uid' => $uid,
            'item_id' => $itemId,
        ]) !== null;
    }
}
