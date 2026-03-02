<?php

class FriendListService{
    public static function getFriendsForR2FlashNeighborFlow($playerObj){
        $friendData = $playerObj->getPlayerDataForNeighbor();
        $fvFriends = [];
        foreach ($friendData as $friend){
            $fvFriends[] =  (object) [
                "uid" => $friend['uid'],
                "name" => $friend['name'],
                "first_name" => $friend['firstname'],
                "last_name" => $friend['lastname'],
                "is_app_user" => true,
                "valid" => true,
                "allowed_restrictions" => false,
                "pic_square" => "",
                "pic_big" => ""
            ];
        }

        $currentNeighbors = $playerObj->getCurrentNeighbors();
        $data["data"] = [
            "requestedFriends" => (object)[
                "FarmVille" => $fvFriends,
                "CurrentAllNeighbor" => $currentNeighbors
            ]
        ];

        return $data;
    }
}