<?php
/**
 * Environment Configuration Loader
 * Loads configuration from .env file
 */

class EnvLoader {
    private static $loaded = false;
    private static $env = [];
    
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            throw new Exception('.env file not found at: ' . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Convert boolean strings
                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = is_float($value + 0) ? (float)$value : (int)$value;
                }
                
                self::$env[$key] = $value;
                
                // Set as environment variable if not already set
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        self::load();
        return self::$env[$key] ?? $_ENV[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        self::$env[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
    
    public static function all() {
        self::load();
        return self::$env;
    }
    
    public static function has($key) {
        self::load();
        return isset(self::$env[$key]) || isset($_ENV[$key]);
    }
}

// Helper function for easy access
function env($key, $default = null) {
    return EnvLoader::get($key, $default);
}

// Load environment variables
EnvLoader::load();
?>
