<?php

class ZAPIClientService
{
    
    public static function getHarvestEmailPref($playerObj, $request, $market = null)
    {
        return ["data" => null];
    }

    
    public static function setHarvestEmailPref($playerObj, $request, $market = null)
    {
        return ["data" => ["success" => true]];
    }
}
