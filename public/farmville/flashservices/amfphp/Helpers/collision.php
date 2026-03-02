<?php

require_once AMFPHP_ROOTPATH . "Helpers/general_functions.php";

use App\Helpers\ObjectHelper;

class CollisionDetector
{
    
    private static array $sizeCache = [];

    
    private const PLOT_SIZE = 4;

    
    private static function extractPosition(object $obj): array
    {
        [$posX, $posY] = ObjectHelper::getPosition($obj);
        return ['x' => $posX, 'y' => $posY];
    }

    
    public static function getItemSize(?string $itemName): array
    {
        if ($itemName === null || $itemName === '') {
            return ['sizeX' => 1, 'sizeY' => 1];
        }

        if (isset(self::$sizeCache[$itemName])) {
            return self::$sizeCache[$itemName];
        }

        $itemData = getItemByName($itemName, "db");
        
        $sizeX = 1;
        $sizeY = 1;
        
        if ($itemData) {
            $sizeX = isset($itemData['sizeX']) ? (int)$itemData['sizeX'] : 1;
            $sizeY = isset($itemData['sizeY']) ? (int)$itemData['sizeY'] : 1;
        }

        if ($sizeX < 1) $sizeX = 1;
        if ($sizeY < 1) $sizeY = 1;

        self::$sizeCache[$itemName] = ['sizeX' => $sizeX, 'sizeY' => $sizeY];
        
        return self::$sizeCache[$itemName];
    }

    
    public static function getSizeByClassName(?string $className): array
    {
        if ($className === null) {
            return ['sizeX' => 1, 'sizeY' => 1];
        }

        if (stripos($className, 'Plot') !== false) {
            return ['sizeX' => self::PLOT_SIZE, 'sizeY' => self::PLOT_SIZE];
        }

        return ['sizeX' => 1, 'sizeY' => 1];
    }

    
    public static function getBoundingBox(object $obj): ?array
    {
        $pos = self::extractPosition($obj);

        if ($pos['x'] === null || $pos['y'] === null) {
            return null;
        }

        $posX = (int)$pos['x'];
        $posY = (int)$pos['y'];

        $itemName = $obj->itemName ?? null;
        $className = $obj->className ?? null;

        if ($itemName) {
            $size = self::getItemSize($itemName);
        } else {
            $size = self::getSizeByClassName($className);
        }

        return [
            'x1' => $posX,
            'y1' => $posY,
            'x2' => $posX + $size['sizeX'],
            'y2' => $posY + $size['sizeY']
        ];
    }

    
    public static function boxesOverlap(?array $box1, ?array $box2): bool
    {
        if ($box1 === null || $box2 === null) {
            return false;
        }

        $noOverlap = $box1['x2'] <= $box2['x1'] ||
                     $box2['x2'] <= $box1['x1'] ||
                     $box1['y2'] <= $box2['y1'] ||
                     $box2['y2'] <= $box1['y1'];

        return !$noOverlap;
    }

    
    public static function isSquareObject(object $obj): bool
    {
        $itemName = $obj->itemName ?? null;
        $className = $obj->className ?? null;

        if ($itemName) {
            $size = self::getItemSize($itemName);
        } else {
            $size = self::getSizeByClassName($className);
        }

        return $size['sizeX'] === $size['sizeY'] && $size['sizeX'] > 1;
    }

    
    public static function checkCollision(object $newObj, array $existingObjects, int|string|null $excludeKey = null): array
    {
        $newClassName = $newObj->className ?? '';
        
        if (stripos($newClassName, 'Plot') === false) {
            return ['collides' => false, 'collidingObject' => null];
        }

        $newBox = self::getBoundingBox($newObj);
        
        if ($newBox === null) {
            return ['collides' => false, 'collidingObject' => null];
        }

        foreach ($existingObjects as $key => $existingObj) {
            if ($excludeKey !== null && $key === $excludeKey) {
                continue;
            }

            if (isset($existingObj->deleted) && $existingObj->deleted) {
                continue;
            }

            $existingClassName = $existingObj->className ?? '';
            
            if (stripos($existingClassName, 'Plot') === false) {
                continue;
            }

            $existingBox = self::getBoundingBox($existingObj);

            if (self::boxesOverlap($newBox, $existingBox)) {
                return ['collides' => true, 'collidingObject' => $existingObj];
            }
        }

        return ['collides' => false, 'collidingObject' => null];
    }

    
    public static function findObjectAtPosition(int $posX, int $posY, array $existingObjects): int|string|null
    {
        foreach ($existingObjects as $key => $obj) {
            if (isset($obj->deleted) && $obj->deleted) {
                continue;
            }

            $pos = self::extractPosition($obj);

            if ($pos['x'] !== null && $pos['y'] !== null) {
                if ((int)$pos['x'] === $posX && (int)$pos['y'] === $posY) {
                    return $key;
                }
            }
        }

        return null;
    }

    
    public static function findObjectCoveringPosition(int $posX, int $posY, array $existingObjects): int|string|null
    {
        foreach ($existingObjects as $key => $obj) {
            if (isset($obj->deleted) && $obj->deleted) {
                continue;
            }

            $box = self::getBoundingBox($obj);
            
            if ($box === null) {
                continue;
            }

            if ($posX >= $box['x1'] && $posX < $box['x2'] &&
                $posY >= $box['y1'] && $posY < $box['y2']) {
                return $key;
            }
        }

        return null;
    }

    
    public static function validatePlacement(object $newObj, array $existingObjects, ?string $action = null): array
    {
        $pos = self::extractPosition($newObj);

        if ($pos['x'] === null || $pos['y'] === null) {
            return ['valid' => false, 'existingKey' => null, 'reason' => 'invalid_position'];
        }

        $posX = (int)$pos['x'];
        $posY = (int)$pos['y'];

        $exactMatch = self::findObjectAtPosition($posX, $posY, $existingObjects);
        if ($exactMatch !== null) {
            return ['valid' => true, 'existingKey' => $exactMatch, 'reason' => 'exact_position_exists'];
        }

        $collision = self::checkCollision($newObj, $existingObjects);
        if ($collision['collides']) {
            return ['valid' => false, 'existingKey' => null, 'reason' => 'collision_detected'];
        }

        return ['valid' => true, 'existingKey' => null, 'reason' => null];
    }

    
    public static function clearCache(): void
    {
        self::$sizeCache = [];
    }
}
