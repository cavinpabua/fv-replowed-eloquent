<?php

namespace App\Helpers;

class ObjectHelper
{
    public static function getPosition($obj): array
    {
        $posX = isset($obj->position) ? ($obj->position->x ?? ($obj->position['x'] ?? null)) : null;
        $posY = isset($obj->position) ? ($obj->position->y ?? ($obj->position['y'] ?? null)) : null;
        $posZ = isset($obj->position) ? ($obj->position->z ?? ($obj->position['z'] ?? 0)) : 0;

        return [$posX, $posY, $posZ];
    }

    public static function extractScalar($value)
    {
        if (is_array($value)) {
            if (count($value) > 1 && is_string($value[0])) {
                return implode(' ', $value);
            }
            return $value[0] ?? null;
        }
        return $value;
    }
}
