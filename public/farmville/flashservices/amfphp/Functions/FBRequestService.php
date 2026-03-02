<?php 

class FBRequestService{
    public static function sendInviteRequest($playerObj, $request){
        foreach($request->params[0] as $uid){
            $playerObj->setPendingNeighbors($uid);
        }

        return [];
    }
}