<?php

class LonelyCowService
{
    
    public static function getRewardAndFeedData($playerObj, $request, $market)
    {
        $data = array();
        $animalName = $request->params[0] ?? "horse_xhf_octoberfestival";

        $data["data"] = [
            "feedData" => [
                "animalName" => $animalName,
            ]
        ];

        return $data;
    }


    public static function createLonelyAnimal($playerObj, $request, $market)
    {
        $data = array();
        $data["data"] = [0, "horse_xhf_octoberfestival"];
        return $data;
    }
}
