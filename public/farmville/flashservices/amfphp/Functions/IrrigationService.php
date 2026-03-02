<?php

require_once AMFPHP_ROOTPATH . "Helpers/general_functions.php";
require_once AMFPHP_ROOTPATH . "Helpers/user_resources.php";

class IrrigationService
{

    private static $waterPackages = [
        'pkg_consume_irrigation_water_100' => ['water' => 100, 'cash' => 2],
        'pkg_consume_irrigation_water_500' => ['water' => 500, 'cash' => 8],
        'pkg_consume_irrigation_water_1000' => ['water' => 1000, 'cash' => 14],
        'pkg_consume_irrigation_water_2000' => ['water' => 2000, 'cash' => 24],
    ];

    
    public static function onIrrigationAction($playerObj, $request, $market = null)
    {
        $uid = $playerObj->getUid();
        $action = $request->params[0] ?? null;
        $actionItems = $request->params[1] ?? [];
        $extraParams = $request->params[2] ?? [];
        $worldType = getCurrentWorldType($uid);

        if (is_object($actionItems)) {
            $actionItems = get_object_vars($actionItems);
        }

        $totalWaterAdded = 0;
        $totalWaterUsed = 0;
        $plotsWatered = 0;

        foreach ($actionItems as $item) {
            if (is_object($item)) {
                $item = get_object_vars($item);
            }

            if (isset($item['id']) && $action === 'waterPlots') {
                $plotsWatered++;
                $totalWaterUsed++;
            }

            $type = $item['type'] ?? null;
            $value = (int) ($item['value'] ?? 0);

            if ($type === 'irrigation' && $value > 0) {
                $totalWaterAdded += $value;
            }
        }

        if ($totalWaterUsed > 0) {
            useWater($uid, $totalWaterUsed, $worldType);
        }

        if ($totalWaterAdded > 0) {
            $newAmount = addWater($uid, $totalWaterAdded, $worldType);

            return [
                "data" => [
                    "success" => true,
                    "waterAdded" => $totalWaterAdded,
                    "waterUsed" => $totalWaterUsed,
                    "waterAmount" => $newAmount
                ]
            ];
        }

        return [
            "data" => [
                "success" => true,
                "waterAdded" => 0,
                "waterUsed" => $totalWaterUsed,
                "plotsWatered" => $plotsWatered,
                "waterAmount" => getWaterAmount($uid, $worldType)
            ]
        ];
    }

    
    public static function getWaterStatus($playerObj, $request, $market = null)
    {
        $uid = $playerObj->getUid();
        $worldType = $request->params[0] ?? 'farm';

        $waterAmount = getWaterAmount($uid, $worldType);
        $irrigationData = getIrrigationData($uid);

        return [
            "data" => [
                "success" => true,
                "waterAmount" => $waterAmount,
                "maxWater" => IRRIGATION_MAX_WATER,
                "irrigationData" => $irrigationData
            ]
        ];
    }

    
    public static function useWaterForPlots($playerObj, $request, $market = null)
    {
        $uid = $playerObj->getUid();
        $amount = (int) ($request->params[0] ?? 0);
        $worldType = $request->params[1] ?? 'farm';

        if ($amount <= 0) {
            return [
                "data" => ["success" => false, "error" => "Invalid amount"]
            ];
        }

        $success = useWater($uid, $amount, $worldType);

        if (!$success) {
            return [
                "data" => ["success" => false, "error" => "Not enough water"]
            ];
        }

        $newAmount = getWaterAmount($uid, $worldType);

        return [
            "data" => [
                "success" => true,
                "waterUsed" => $amount,
                "waterAmount" => $newAmount
            ]
        ];
    }

    
    public static function addWaterFromHarvest($playerObj, $request, $market = null)
    {
        $uid = $playerObj->getUid();
        $amount = (int) ($request->params[0] ?? 0);
        $worldType = $request->params[1] ?? 'farm';

        if ($amount <= 0) {
            return [
                "data" => ["success" => false, "error" => "Invalid amount"]
            ];
        }

        $newAmount = addWater($uid, $amount, $worldType);

        return [
            "data" => [
                "success" => true,
                "waterAdded" => $amount,
                "waterAmount" => $newAmount
            ]
        ];
    }

    
    public static function consumeIrrigationPackages($playerObj, $request, $market = null)
    {
        $uid = $playerObj->getUid();
        $quantity = (int) ($request->params[1] ?? 0);

        if ($quantity <= 0) {
            return [
                "data" => ["success" => false, "error" => "Invalid quantity"]
            ];
        }

        $worldType = getCurrentWorldType($uid);

        if (!removeGiftByCode($uid, IRRIGATION_WATER_ITEM_CODE, $quantity)) {
            return [
                "data" => ["success" => false, "error" => "Not enough irrigation packages"]
            ];
        }

        $newAmount = addWater($uid, $quantity, $worldType);

        return [
            "data" => [
                "success" => true,
                "waterAdded" => $quantity,
                "waterAmount" => $newAmount
            ]
        ];
    }

    
    public static function buyWaterPackage($playerObj, $request, $market = null)
    {
        $uid = $playerObj->getUid();
        $packageName = $request->params[0] ?? null;

        if (!$packageName || !isset(self::$waterPackages[$packageName])) {
            return [
                "data" => ["success" => false, "error" => "Invalid package"]
            ];
        }

        $package = self::$waterPackages[$packageName];
        $waterAmount = $package['water'];
        $cashCost = $package['cash'];

        $currentCash = UserResources::getCash($uid);
        if ($currentCash < $cashCost) {
            return [
                "data" => ["success" => false, "error" => "Not enough cash"]
            ];
        }

        if (!UserResources::removeCash($uid, $cashCost)) {
            return [
                "data" => ["success" => false, "error" => "Failed to deduct cash"]
            ];
        }

        $worldType = getCurrentWorldType($uid);
        $newWaterAmount = addWater($uid, $waterAmount, $worldType);
        $newCash = UserResources::getCash($uid);

        return [
            "data" => [
                "success" => true,
                "waterAdded" => $waterAmount,
                "waterAmount" => $newWaterAmount,
                "cashSpent" => $cashCost,
                "newCash" => $newCash
            ]
        ];
    }

    
    public static function getWaterPackages($playerObj, $request, $market = null)
    {
        $packages = [];
        foreach (self::$waterPackages as $name => $data) {
            $packages[] = [
                "name" => $name,
                "water" => $data['water'],
                "cash" => $data['cash']
            ];
        }

        return [
            "data" => [
                "success" => true,
                "packages" => $packages
            ]
        ];
    }
}
