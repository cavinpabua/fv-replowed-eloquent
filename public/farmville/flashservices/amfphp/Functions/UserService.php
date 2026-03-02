<?php

require_once AMFPHP_ROOTPATH . "Helpers/globals.php";
require_once AMFPHP_ROOTPATH . "Helpers/player.php";
require_once AMFPHP_ROOTPATH . "Helpers/market_transactions.php";
require_once AMFPHP_ROOTPATH . "Helpers/logger.php";
require_once AMFPHP_ROOTPATH . "Helpers/constants.php";
require_once AMFPHP_ROOTPATH . "Helpers/hud_icons.php";
require_once AMFPHP_ROOTPATH . "Functions/AvatarService.php";

use App\Helpers\JsonHelper;
use App\Models\UserMeta;

class UserService{
    function __construct()
    {

    }

    public static function initUser($playerObj, $request){
        return array(
            "data" => $playerObj->getData($request)
        );
    }

    public static function postInit($playerObj = null){
        $neighborCount = 0;
        $unlockedAvatarItems = new stdClass();
        $avatarConfigurations = [
            "male" => new stdClass(),
            "female" => new stdClass()
        ];
        $playerLevel = 0;

        if ($playerObj) {
            $uid = $playerObj->getUid();
            $currNeighbors = get_meta($uid, 'current_neighbors');
            if ($currNeighbors) {
                $uids = @unserialize($currNeighbors) ?: [];
                $neighborCount = count($uids);
            }

            $unlockedAvatarItems = AvatarService::getUnlockedItems($uid);
            $avatarConfigurations = AvatarService::getConfigurations($uid);

            $userMeta = UserMeta::where('uid', $uid)->first();
            if ($userMeta) {
                $xp = (int) ($userMeta->xp ?? 0);
                $playerLevel = floor($xp / 500) + 1;
            }

            Logger::debug('UserService', "postInit called for uid=$uid, level=$playerLevel, neighborCount=$neighborCount");
        }

        $data["data"] = array(
            "postInitTimestampMetric" => time(),
            "friendsFertilized" => array(),
            "totalFriendsFertilized" => 0,
            "friendsFedAnimals" => array(),
            "totalFriendsFedAnimals" => 0,
            "showBookmark" => true,
            "showToolbarThankYou" => false,
            "toolbarGiftName" => "",
            "isAbleToPlayMusic" => true,
            "FOFData" => array(),
            "prereqDSData" => array(),
            "neighborCount" => $neighborCount,
            "giftsendBlacklist" => array(),
            "fcSlotMachineRewards" => array(
                "allRewards" => array(),
                "mgRewards" => array()
            ),
            "hudIcons" => getHudIcons(),
            "crossGameGiftingState" => null,
            "avatarState" => array(
                "unlocked" => $unlockedAvatarItems,
                "configurations" => $avatarConfigurations
            ),
            "breedingState" => null,
            "w2wState" => null,
            "bestSellers" => null,
            "completedQuests" => null,
            "completedReplayableQuests" => null,
            "pricingTests" => null,
            "buildingActions" => null,
            "lastPphActionType" => "PphAction",
            "communityGoalsData" => null,
            "turtleInnovationData" => array(),
            "dragonCollection" => null,
            "worldCurrencies" => array(),
            "lotteryData" => array(),
            "popupTwitterDialog" => false,
            "storageExpansionBuildingId" => null,
            "marketView" => null
        );

        return $data;
    }

    public static function getBalance($playerObj = null, $request = null, $market = null){
        global $db;

        $gold = 100000;
        $cash = 10;

        if ($playerObj) {
            $uid = $playerObj->getUid();
            $resources = UserMeta::loadResources($uid);
            $gold = $resources['gold'];
            $cash = $resources['cash'];
        }

        $data["data"] = array(
            "gold" => $gold,
            "cash" => $cash
        );

        return $data;
    }

    public static function getMOTD(){
        $data["data"] = array(
            "motdData" => array(
                "name" => "PAOK",
            )
        );

        return $data;
    }

    public static function getGifts($playerObj, $request = null, $market = null){
        $uid = $playerObj->getUid();
        $data["data"] = array(
            "storageData" => array(
                "-6" => buildGiftBoxStorageData($uid)
            )
        );
        return $data;
    }

    public static function buyItemPutGiftbox($playerObj, $request, $market = null){
        $uid = $playerObj->getUid();
        $itemName = $request->params[0] ?? null;
        $quantity = (int) ($request->params[1] ?? 1);

        if ($itemName) {
            $item = getItemByName($itemName, "db");
            if ($item && isset($item['code'])) {
                $cost = (int) ($item['cost'] ?? 0);
                $currency = $item['market'] ?? 'gold';
                if ($currency === 'cash') {
                    UserResources::removeCash($uid, $cost * $quantity);
                } else {
                    UserResources::removeGold($uid, $cost * $quantity);
                }
                addGiftByCode($uid, $item['code'], $quantity);
            }
        }

        return array("data" => array());
    }


    public static function sellStoredItem($playerObj, $request, $market = null){
        $uid = $playerObj->getUid();

        $param0 = $request->params[0] ?? null;
        $itemCode = is_object($param0) ? ($param0->itemCode ?? $param0->code ?? null) : $param0;
        $inventoryId = (int) ($request->params[2] ?? HOME_INVENTORY_ID);
        $quantity = (int) ($request->params[3] ?? 1);

        if (!$itemCode || $quantity <= 0) {
            return ["data" => ["success" => false, "error" => "Invalid parameters"]];
        }

        $item = getItemByCode($itemCode);
        if (!$item) {
            return ["data" => ["success" => false, "error" => "Item not found"]];
        }

        $removed = removeFromInventoryStorage($uid, $itemCode, $quantity);
        if (!$removed) {
            return ["data" => ["success" => false, "error" => "Item not in storage"]];
        }

        $sellPrice = 0;
        if (isset($item['sellPrice'])) {
            $sellPrice = (int) $item['sellPrice'];
        } elseif (isset($item['coinYield'])) {
            $sellPrice = (int) floor($item['coinYield'] / 2);
        } elseif (isset($item['cost']) && ($item['market'] ?? 'gold') === 'gold') {
            $sellPrice = (int) floor($item['cost'] / 2);
        }
        $totalGold = $sellPrice * $quantity;
        
        if ($totalGold > 0) {
            UserResources::addGold($uid, $totalGold);
        }

        return [
            "data" => [
                "sellable" => true,
                "qtySold" => $quantity,
                "sellAmounts" => [
                    "coins" => $totalGold
                ]
            ]
        ];
    }

    public static function r2InterstitialPostInit($playerObj = null, $request = null, $market = null){
        $data["data"] = array();
        return $data;
    }

    public static function getTargetingGroups($playerObj = null, $request = null, $market = null){
        $data["data"] = array(
            "groups" => array()
        );
        return $data;
    }

    public static function setSeenFlag($player, $request){
        $uid = $player->getUid();

        if (is_numeric($uid)){
            $userMeta = UserMeta::where('uid', $uid)->first();

            if ($userMeta === null) {
                return [];
            }

            $flags = @unserialize($userMeta->seenFlags) ?: [];
            $toAdd = $request->params[0];
            $flags[$toAdd] = true;

            $userMeta->seenFlags = serialize($flags);
            $userMeta->save();
        }

        return [];
    }

    public static function getGiftboxErrorState($playerObj, $request = null, $market = null){
        $data["data"] = array(
            "giftboxError" => 0
        );
        return $data;
    }

    public static function getUnwitherRingData($playerObj, $request = null, $market = null){
        $uid = $playerObj->getUid();
        $ringId = $request->params[0] ?? 0;

        if (!$ringId) {
            return ["data" => ["success" => false]];
        }

        $worldType = getCurrentWorldType($uid);
        $worldId = getWorldId($uid, $worldType);

        if (!$worldId) {
            return ["data" => ["success" => false]];
        }

        $ring = \App\Models\WorldObject::where('world_id', $worldId)
            ->where('object_id', $ringId)
            ->where('deleted', false)
            ->first();

        if (!$ring) {
            return ["data" => ["success" => false]];
        }

        $components = $ring->components;

        if (is_string($components)) {
            $components = JsonHelper::safeDecode($components, false);
        }

        $ringItemName = $ring->item_name;
        $ringItemData = getItemByName($ringItemName, "db");
        $ringItemCode = $ringItemData ? ($ringItemData['code'] ?? null) : null;

        if (!$ringItemCode) {
            $ringItemCode = "1a";
        }

        $extraItemData = [
            "sender" => -1,
            "ringType" => $ringItemCode,
            "message" => ""
        ];

        if ($components) {
            if (isset($components->sender)) {
                $extraItemData["sender"] = $components->sender;
            }
            if (isset($components->message)) {
                $extraItemData["message"] = $components->message;
            }
        }

        return [
            "data" => [
                "success" => true,
                "extraItemData" => $extraItemData
            ]
        ];
    }

    public static function toggleWither($playerObj, $request = null, $market = null){
        $uid = $playerObj->getUid();
        $ringId = $request->params[0] ?? 0;

        $worldType = getCurrentWorldType($uid);
        $worldId = getWorldId($uid, $worldType);

        if (!$worldId) {
            return ["data" => ["success" => false, "error" => "no_world"]];
        }

        $ring = \App\Models\WorldObject::where('world_id', $worldId)
            ->where('object_id', $ringId)
            ->where('deleted', false)
            ->first();

        if (!$ring) {
            return ["data" => ["success" => false, "error" => "ring_not_found"]];
        }

        $components = $ring->components;

        if (is_string($components)) {
            $components = JsonHelper::safeDecode($components, false, new \stdClass());
        } elseif (!is_object($components) || $components === null) {
            $components = new \stdClass();
        }

        $currentActive = property_exists($components, 'active') ? $components->active : true;
        $newActive = !$currentActive;

        $components->active = $newActive;

        $ring->components = $components;
        $ring->save();

        invalidateWorldCache($uid, $worldType);

        return [
            "data" => [
                "success" => true,
                "witherOn" => !$newActive
            ]
        ];
    }

    public static function customizeUnwitherRingData($playerObj, $request = null, $market = null){
        $uid = $playerObj->getUid();
        $ringData = $request->params[0] ?? null;
        $ringId = $request->params[1] ?? 0;

        if (!$ringData || !$ringId) {
            return ["data" => ["success" => false, "error" => "missing_params"]];
        }

        $worldType = getCurrentWorldType($uid);
        $worldId = getWorldId($uid, $worldType);

        if (!$worldId) {
            return ["data" => ["success" => false, "error" => "no_world"]];
        }

        $ring = \App\Models\WorldObject::where('world_id', $worldId)
            ->where('object_id', $ringId)
            ->where('deleted', false)
            ->first();

        if (!$ring) {
            return ["data" => ["success" => false, "error" => "ring_not_found"]];
        }

        $components = $ring->components;
        if (is_string($components)) {
            $components = JsonHelper::safeDecode($components, false, new \stdClass());
        } elseif (!is_object($components) || $components === null) {
            $components = new \stdClass();
        }

        if (isset($ringData->metal)) {
            $components->metal = $ringData->metal;
        }
        if (isset($ringData->gem)) {
            $components->gem = $ringData->gem;
        }
        if (isset($ringData->message)) {
            $components->message = $ringData->message;
        }

        if (isset($ringData->metal)) {
            $ringType = $ringData->metal;
            if (isset($ringData->gem) && $ringData->gem !== 'none' && $ringData->gem !== '') {
                $ringType .= $ringData->gem;
            }
            $components->ringType = $ringType;
        }

        if (!isset($components->sender)) {
            $components->sender = $uid;
        }

        if (!isset($components->active)) {
            $components->active = true;
        }

        $ring->components = $components;
        $ring->save();

        invalidateWorldCache($uid, $worldType);

        return ["data" => ["success" => true]];
    }

    public static function updateFeatureFrequencyTimestamp($playerObj, $request = null, $market = null){
        $uid = $playerObj->getUid();
        $featureName = $request->params[0] ?? null;

        if (!$featureName || !is_string($featureName)) {
            return array("data" => array("success" => false));
        }

        $raw = get_meta($uid, 'feature_frequency_timestamps');
        $timestamps = $raw ? (@unserialize($raw) ?: []) : [];

        $timestamps[$featureName] = time();

        set_meta($uid, 'feature_frequency_timestamps', serialize($timestamps));

        return array("data" => array("success" => true));
    }

    public static function updateWorldScoreLevelUp($playerObj = null, $request = null, $market = null){
        return array("data" => array());
    }


    public static function getSendFlashGiftFriends($playerObj, $request = null, $market = null){
        $uid = $playerObj->getUid();
        $itemName = $request->params[0] ?? null;

        $item = $itemName ? getItemByName($itemName, "db") : null;
        $itemCode = $item ? ($item['code'] ?? null) : null;
        
        $alreadySent = [];
        if ($itemCode) {
            $sentKey = "flash_gift_sent_" . $itemCode;
            $alreadySentRaw = get_meta($uid, $sentKey);
            $alreadySent = $alreadySentRaw ? (@unserialize($alreadySentRaw) ?: []) : [];
            $alreadySent = array_map('intval', $alreadySent);
        }

        $currNeighbors = get_meta($uid, 'current_neighbors');
        $neighborUids = $currNeighbors ? (@unserialize($currNeighbors) ?: []) : [];

        $friends = [];
        if (!empty($neighborUids)) {
            $neighborUids = array_values(array_unique(array_filter($neighborUids, 'is_numeric')));
            
            $usersData = \App\Models\User::join('usermeta', 'users.uid', '=', 'usermeta.uid')
                ->whereIn('users.uid', $neighborUids)
                ->select([
                    'users.uid as uid',
                    'users.name as name',
                    'usermeta.firstName as firstName',
                    'usermeta.lastName as lastName',
                    'usermeta.xp as xp',
                    'usermeta.profile_picture as profile_picture'
                ])
                ->get();

            foreach ($usersData as $userData) {
                $friendUid = (int) $userData->uid;

                if (in_array($friendUid, $alreadySent)) {
                    continue;
                }

                $xp = (int) ($userData->xp ?? 0);
                $level = floor($xp / 500) + 1;

                $displayName = trim(($userData->firstName ?? '') . ' ' . ($userData->lastName ?? ''));
                if (empty($displayName)) {
                    $displayName = $userData->name ?? 'Neighbor';
                }

                $picSquare = $userData->profile_picture ?: "https://fv-assets.s3.us-east-005.backblazeb2.com/profile-pictures/default_avatar.png";

                $friends[] = [
                    "uid" => (string) $userData->uid,
                    "name" => $displayName,
                    "pic_square" => $picSquare,
                    "level" => (int) $level,
                    "valid" => true
                ];
            }
        }

        return [
            "data" => [
                "requestedFriends" => [
                    "FarmVille" => $friends
                ]
            ]
        ];
    }
}
