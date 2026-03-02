<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FriendSet extends Model
{
    protected $table = 'friend_sets';

    protected $fillable = [
        'uid',
        'code',
        'fs_index',
        'friends',
        'pending',
        'bought_count',
        'progress_state',
        'start_time',
        'world_code',
        'reward_link',
    ];

    protected $casts = [
        'fs_index' => 'integer',
        'bought_count' => 'integer',
        'progress_state' => 'integer',
        'start_time' => 'integer',
    ];

    public static function getLatest(string|int $uid, string $code): ?static
    {
        return static::where('uid', $uid)
            ->where('code', $code)
            ->orderByDesc('id')
            ->first();
    }

    public static function getByIndex(string|int $uid, string $code, int $index): ?static
    {
        return static::where('uid', $uid)
            ->where('code', $code)
            ->where('fs_index', $index)
            ->first();
    }

    public static function createSet(string|int $uid, string $code, int $index, int $progressState, string $friends = '', string $pending = ''): static
    {
        return static::create([
            'uid' => $uid,
            'code' => $code,
            'fs_index' => $index,
            'progress_state' => $progressState,
            'bought_count' => 0,
            'friends' => $friends,
            'pending' => $pending,
            'start_time' => time(),
        ]);
    }

    public function updateProgress(int $progressState): bool
    {
        return $this->update(['progress_state' => $progressState]);
    }

    public function completeWithCash(int $boughtCount, int $progressState): bool
    {
        return $this->update([
            'bought_count' => $boughtCount,
            'progress_state' => $progressState,
        ]);
    }

    public function getFriendsArrayAttribute(): array
    {
        if (empty($this->friends)) {
            return [];
        }
        return @unserialize($this->friends) ?: [];
    }

    public function getPendingArrayAttribute(): array
    {
        if (empty($this->pending)) {
            return [];
        }
        return @unserialize($this->pending) ?: [];
    }
}
