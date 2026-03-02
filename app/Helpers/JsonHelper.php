<?php

namespace App\Helpers;

class JsonHelper
{
    public static function safeEncode($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        if (is_string($value)) {
            if (strlen($value) >= 2 && $value[0] === '"') {
                $decoded = json_decode($value);
                if ($decoded !== null || $value === '"null"') {
                    return is_string($decoded) ? $decoded : json_encode($decoded);
                }
            }
            $decoded = json_decode($value);
            if ($decoded !== null) {
                return $value;
            }
        }
        return $value;
    }

    public static function safeDecode($value, bool $assoc = false, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $decoded = json_decode($value, $assoc);
        if (is_string($decoded) && strlen($decoded) > 0) {
            if ($decoded === '{}' || $decoded === '[]' || $decoded[0] === '{' || $decoded[0] === '[') {
                $doubleDecoded = json_decode($decoded, $assoc);
                if ($doubleDecoded !== null || $decoded === 'null') {
                    return $doubleDecoded ?? $default;
                }
            }
        }
        return $decoded ?? $default;
    }
}
