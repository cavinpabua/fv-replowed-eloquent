<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWorld extends Model
{
    protected $table = 'userworlds';

    protected $fillable = [
        'uid', 'type', 'sizeX', 'sizeY', 'messageManager'
    ];

    protected $casts = [
        'sizeX' => 'integer',
        'sizeY' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uid');
    }

    public static function getByType(string|int $uid, string $type = 'main'): ?static
    {
        return static::where('uid', $uid)
            ->where('type', $type)
            ->first();
    }

    public static function getWorldId(string|int $uid, string $type = 'main'): ?int
    {
        $world = static::where('uid', $uid)
            ->where('type', $type)
            ->first(['id']);

        return $world ? $world->id : null;
    }

    public static function createWorld(string|int $uid, string $type, int $sizeX, int $sizeY): static
    {
        return static::create([
            'uid' => $uid,
            'type' => $type,
            'sizeX' => $sizeX,
            'sizeY' => $sizeY,
            'messageManager' => '',
        ]);
    }

    public static function updateSize(string|int $uid, string $type, int $sizeX, int $sizeY): bool
    {
        return static::where('uid', $uid)
            ->where('type', $type)
            ->update([
                'sizeX' => $sizeX,
                'sizeY' => $sizeY,
            ]) > 0;
    }

    public static function expand(int $worldId, int $sizeX, int $sizeY): bool
    {
        return static::where('id', $worldId)
            ->update([
                'sizeX' => $sizeX,
                'sizeY' => $sizeY,
            ]) > 0;
    }

    public static function saveObjects(string|int $uid, string $type, string $objects): bool
    {
        return true;
    }

    public static function updateMessageManager(string|int $uid, string $type, string $messageManager): bool
    {
        return static::where('uid', $uid)
            ->where('type', $type)
            ->update(['messageManager' => $messageManager]) > 0;
    }

    public static function saveWorld(string|int $uid, string $type, int $sizeX, int $sizeY, ?string $messageManager = null): bool
    {
        $data = [
            'sizeX' => $sizeX,
            'sizeY' => $sizeY,
        ];

        if ($messageManager !== null) {
            $data['messageManager'] = $messageManager;
        }

        return static::where('uid', $uid)
            ->where('type', $type)
            ->update($data) > 0;
    }
}
