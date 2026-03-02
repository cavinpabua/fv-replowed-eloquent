<?php 

class FriendSetService{
    public static function getBatchFriendSetData($playerObj, $request){
        $data["data"] = array();
        return $data;
    }

    
    public static function friendSetCompleteSeen($playerObj, $request, $market = null){
        $uid = $playerObj->getUid();
        $friendSetCode = $request->params[0] ?? null;
        $remove = $request->params[1] ?? false;

        if ($friendSetCode && $uid) {
            $seenSets = get_meta($uid, 'friendSetsSeen');
            $seenSets = $seenSets ? (@unserialize($seenSets) ?: []) : [];

            if ($remove) {
                $seenSets = array_filter($seenSets, function($code) use ($friendSetCode) {
                    return $code !== $friendSetCode;
                });
            } else {
                if (!in_array($friendSetCode, $seenSets)) {
                    $seenSets[] = $friendSetCode;
                }
            }

            set_meta($uid, 'friendSetsSeen', serialize($seenSets));
        }

        $data["data"] = array();
        return $data;
    }
}