<?php

class NeighborActionService
{
    public static function getVisitGiftW2W($playerObj, $request, $market = null)
    {
        $data["data"] = array(
            "rewardLink" => null,
            "itemCode" => null,
            "goodieBagRewardItemCode" => null,
            "fertilizeRewardLink" => null
        );

        return $data;
    }
}
