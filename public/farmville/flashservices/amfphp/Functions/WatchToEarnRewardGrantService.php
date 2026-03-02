<?php

class WatchToEarnRewardGrantService
{
    public static function getUserZid($playerObj = null, $request = null, $market = null){
        $data["data"] = array(
            "zid" => $playerObj ? $playerObj->getUid() : "0"
        );
        return $data;
    }
}
