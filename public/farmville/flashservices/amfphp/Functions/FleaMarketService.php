<?php

class FleaMarketService
{
    public static function initialize($playerObj, $request, $market)
    {
        $data = array();
        $data["data"] = array("items" => array(), "balance" => 0);
        return $data;
    }

    public static function resellItem($playerObj, $request, $market)
    {
        $data = array();
        $data["data"] = array("success" => true);
        return $data;
    }
}
