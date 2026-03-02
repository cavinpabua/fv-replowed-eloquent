<?php

class Logger
{
    private static $logFile = null;
    private static $buffer = [];
    private static $initialized = false;
    private static $enabled = null;

    
    private static function isEnabled()
    {
        if (self::$enabled !== null) {
            return self::$enabled;
        }

        self::$enabled = true;

        $envFile = dirname(AMFPHP_ROOTPATH, 4) . '/.env';
        if (file_exists($envFile)) {
            $contents = file_get_contents($envFile);
            if (preg_match('/^FARMVILLE_LOG_ENABLED\s*=\s*(.+)$/m', $contents, $matches)) {
                $value = trim($matches[1], "\"' \t\n\r");
                self::$enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return self::$enabled;
    }

    
    private static function init()
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        if (!self::isEnabled()) {
            return;
        }

        self::$logFile = dirname(AMFPHP_ROOTPATH, 4) . '/storage/logs/farmville.log';

        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        register_shutdown_function([self::class, 'flush']);
    }

    
    public static function log($category, $message, $data = null)
    {
        self::init();
        if (!self::$enabled) return;

        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] [$category] $message";

        if ($data !== null) {
            if (is_object($data)) {
                $data = get_object_vars($data);
            }
            $entry .= " " . json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        self::$buffer[] = $entry;
    }

    
    public static function section($category, $title)
    {
        self::init();
        if (!self::$enabled) return;

        $timestamp = date('Y-m-d H:i:s');
        self::$buffer[] = "\n[$timestamp] [$category] === $title ===";
    }

    
    public static function error($category, $message, $data = null)
    {
        self::init();
        if (!self::$enabled) return;

        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] [$category] ERROR: $message";

        if ($data !== null) {
            if (is_object($data)) {
                $data = get_object_vars($data);
            }
            $entry .= " " . json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        self::$buffer[] = $entry;
    }

    
    public static function debug($category, $message, $data = null)
    {
        self::init();
        if (!self::$enabled) return;

        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] [$category]   $message";

        if ($data !== null) {
            if (is_object($data)) {
                $data = get_object_vars($data);
            }
            $entry .= " " . json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        self::$buffer[] = $entry;
    }

    
    public static function flush()
    {
        if (empty(self::$buffer) || self::$logFile === null) {
            return;
        }

        $content = implode("\n", self::$buffer) . "\n";
        file_put_contents(self::$logFile, $content, FILE_APPEND | LOCK_EX);
        self::$buffer = [];
    }
}
