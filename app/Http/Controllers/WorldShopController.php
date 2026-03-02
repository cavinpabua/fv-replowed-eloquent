<?php

namespace App\Http\Controllers;

use App\Models\PlayerMeta;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorldShopController extends Controller
{
    private const WORLD_PRICE = 200;
    private const META_KEY = 'unlocked_worlds';
    private const FREE_WORLDS = ['farm'];
    private const PURCHASABLE_WORLDS = [
        'england', 'fisherman', 'winterwonderland', 'australia',
        'space', 'candy', 'fforest', 'hlights', 'rainforest', 'oz',
        'mediterranean', 'oasis', 'storybook', 'sleepyhollow', 'toyland',
        'village', 'glen', 'atlantis', 'hallow'
    ];

    public function status()
    {
        $user = Auth::user();
        $uid = $user->uid;

        $userMeta = UserMeta::where('uid', $uid)->first();
        $cash = $userMeta ? $userMeta->cash : 0;
        $purchasedWorlds = $this->getPurchasedWorlds($uid);
        $allUnlocked = array_merge(self::FREE_WORLDS, $purchasedWorlds);

        return response()->json([
            'cash' => $cash,
            'unlockedWorlds' => $allUnlocked,
            'freeWorlds' => self::FREE_WORLDS,
            'purchasedWorlds' => $purchasedWorlds
        ]);
    }

    public function purchase(Request $request)
    {
        $user = Auth::user();
        $uid = $user->uid;
        $worldId = $request->input('worldId');

        if (!in_array($worldId, self::PURCHASABLE_WORLDS)) {
            return response()->json(['success' => false, 'message' => 'Invalid world'], 400);
        }

        $purchasedWorlds = $this->getPurchasedWorlds($uid);
        if (in_array($worldId, $purchasedWorlds)) {
            return response()->json(['success' => false, 'message' => 'World already unlocked'], 400);
        }

        $userMeta = UserMeta::where('uid', $uid)->first();
        if (!$userMeta || $userMeta->cash < self::WORLD_PRICE) {
            return response()->json(['success' => false, 'message' => 'Not enough Farm Cash'], 400);
        }

        $userMeta->cash -= self::WORLD_PRICE;
        $userMeta->save();

        $purchasedWorlds[] = $worldId;
        $this->setPurchasedWorlds($uid, $purchasedWorlds);

        return response()->json([
            'success' => true,
            'worldId' => $worldId,
            'newCash' => $userMeta->cash,
            'message' => "Successfully unlocked {$worldId}!"
        ]);
    }

    private function getPurchasedWorlds(string $uid): array
    {
        $meta = PlayerMeta::where('uid', $uid)
            ->where('meta_key', self::META_KEY)
            ->first();

        if (!$meta || empty($meta->meta_value)) {
            return [];
        }

        $worlds = @unserialize($meta->meta_value);
        return is_array($worlds) ? $worlds : [];
    }

    private function setPurchasedWorlds(string $uid, array $worlds): void
    {
        PlayerMeta::updateOrCreate(
            ['uid' => $uid, 'meta_key' => self::META_KEY],
            ['meta_value' => serialize($worlds)]
        );
    }

    public static function getAllUnlockedWorlds(string $uid): array
    {
        $meta = PlayerMeta::where('uid', $uid)
            ->where('meta_key', self::META_KEY)
            ->first();

        $purchasedWorlds = [];
        if ($meta && !empty($meta->meta_value)) {
            $worlds = @unserialize($meta->meta_value);
            $purchasedWorlds = is_array($worlds) ? $worlds : [];
        }

        return array_merge(self::FREE_WORLDS, $purchasedWorlds);
    }
}
