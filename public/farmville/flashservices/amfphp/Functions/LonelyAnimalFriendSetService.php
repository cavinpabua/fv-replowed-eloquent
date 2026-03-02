<?php
require_once AMFPHP_ROOTPATH . "Helpers/general_functions.php";

class LonelyAnimalFriendSetService
{
    
    public static function getLonelyAnimalFriendSetProgress($playerObj, $request, $market)
    {
        $data = array();
        $uid = $playerObj->getUid();
        $code = $request->params[0] ?? "FS06";
        $worldCode = $request->params[1] ?? "horse_xhf_octoberfestival";

        $fs = getFriendSet($uid, $code);

        if (!$fs) {
            $fs = createFriendSet($uid, $code, $worldCode);
        }

        if (!$fs) {
            $data["data"] = [
                "indexOut"                => 0,
                "madeProgress"           => false,
                "progressState"          => 0,
                "finalFriendReceiveTime" => 0,
                "rewardLink"             => "",
                "uids"                   => new \stdClass(),
                "pending"                => [],
                "boughtFriendCount"      => 0,
                "startTime"              => time(),
            ];
            return $data;
        }

        $uids = json_decode($fs['friends'], true);
        if ($uids === null) $uids = new \stdClass();

        $pending = json_decode($fs['pending'], true);
        if ($pending === null) $pending = [];

        $data["data"] = [
            "indexOut"                => (int) $fs['fs_index'],
            "madeProgress"           => ($fs['progress_state'] > 0),
            "progressState"          => (int) $fs['progress_state'],
            "finalFriendReceiveTime" => 0,
            "rewardLink"             => $fs['reward_link'] ?? "",
            "uids"                   => $uids,
            "pending"                => $pending,
            "boughtFriendCount"      => (int) $fs['bought_count'],
            "startTime"              => (int) $fs['start_time'],
        ];

        return $data;
    }

    
    public static function getLonelyAnimalFriendSetData($playerObj, $request, $market)
    {
        $data = array();
        $uid = $playerObj->getUid();
        $code = $request->params[0] ?? "FS06";
        $fsIndex = (int) ($request->params[1] ?? 0);

        $fs = getFriendSetByIndex($uid, $code, $fsIndex);

        if ($fs) {
            $data["data"] = buildFriendSetResponse($fs);
        } else {
            $data["data"] = [
                "uids"              => new \stdClass(),
                "pending"           => [],
                "boughtFriendCount" => 0,
                "startTime"         => time(),
                "rewardLink"        => "",
            ];
        }

        return $data;
    }

    
    public static function markLonelyAnimalFriendSet($playerObj, $request, $market)
    {
        $data = array();
        $uid = $playerObj->getUid();
        $progress = (int) ($request->params[0] ?? 0);
        $code = $request->params[1] ?? "FS06";
        $fsIndex = (int) ($request->params[2] ?? 0);

        updateProgressState($uid, $code, $fsIndex, $progress);

        $data["data"] = ["success" => true];
        return $data;
    }

    
    public static function completeFriendSet($playerObj, $request, $market)
    {
        $data = array();
        $uid = $playerObj->getUid();
        $code = $request->params[0] ?? "FS06";
        $fsIndex = (int) ($request->params[1] ?? 0);

        $result = completeFriendSetWithCash($uid, $code, $fsIndex);

        $data["data"] = $result;
        return $data;
    }
}
