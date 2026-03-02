<?php

class DailyStatsService
{
    public static function incrementCounter($playerObj, $request, $market = null)
    {
        return ["data" => ["success" => true]];
    }

    public static function resetCounter($playerObj, $request, $market = null)
    {
        return ["data" => ["success" => true]];
    }
}
