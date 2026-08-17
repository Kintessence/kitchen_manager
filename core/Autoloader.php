<?php

namespace KitchenManager\Core;

class Autoloader 
{
    public static function register(): void 
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload(string $class): void 
    {
        $prefix = 'KitchenManager\\';
        $baseDir = KM_PLUGIN_DIR;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }

        // Fallback para caminhos com a pasta modules/ ou core/ minúscula
        $parts = explode('\\', $relativeClass);
        if (!empty($parts)) {
            $parts[0] = strtolower($parts[0]); // core ou modules
            $fallbackFile = $baseDir . implode('/', $parts) . '.php';
            if (file_exists($fallbackFile)) {
                require_once $fallbackFile;
            }
        }
    }
}