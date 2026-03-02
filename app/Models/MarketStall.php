<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketStall extends Model
{
    protected $table = 'market_stalls';

    protected $fillable = [
        'uid',
        'stall_object_id',
        'bushel_item_code',
        'is_configured',
        'date_closed',
        'inventory',
    ];

    protected $casts = [
        'stall_object_id' => 'integer',
        'is_configured' => 'boolean',
        'date_closed' => 'integer',
    ];

    public static function getByUids(array $uids): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($uids)) {
            return collect();
        }

        $now = time();
        return static::whereIn('uid', $uids)
            ->where('is_configured', 1)
            ->where('date_closed', '>', $now)
            ->get();
    }

    public static function getByObjectId(string|int $uid, int $objectId): ?static
    {
        return static::where('uid', $uid)
            ->where('stall_object_id', $objectId)
            ->first();
    }

    public static function getForUser(string|int $uid): \Illuminate\Database\Eloquent\Collection
    {
        $now = time();
        return static::where('uid', $uid)
            ->where('is_configured', 1)
            ->where('date_closed', '>', $now)
            ->get();
    }

    public static function configure(string|int $uid, int $objectId, string $bushelItemCode, int $dateClosed, string $inventoryJson): static
    {
        return static::updateOrCreate(
            ['uid' => $uid, 'stall_object_id' => $objectId],
            [
                'bushel_item_code' => $bushelItemCode,
                'is_configured' => 1,
                'date_closed' => $dateClosed,
                'inventory' => $inventoryJson,
            ]
        );
    }

    public function close(): bool
    {
        return $this->update(['is_configured' => 0, 'inventory' => null]);
    }

    public function getInventoryArrayAttribute(): array
    {
        return json_decode($this->inventory, true) ?: [];
    }
}
