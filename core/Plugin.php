<?php

namespace KitchenManager\Core;

class Plugin 
{
    /**
     * @var ModuleInterface[]
     */
    private array $modules = [];

    public function init(): void 
    {
        $this->discoverAndLoadModules();
    }

    /**
     * Varre a pasta modules/ e inicializa todos os módulos que implementam ModuleInterface
     */
    private function discoverAndLoadModules(): void 
    {
        $modulesDir = KM_PLUGIN_DIR . 'modules/';
        if (!is_dir($modulesDir)) {
            return;
        }

        $dirs = scandir($modulesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $modulePath = $modulesDir . $dir;
            if (!is_dir($modulePath)) {
                continue;
            }

            // Ex: KitchenManager\Modules\Dashboard\DashboardModule
            $className = "KitchenManager\\Modules\\{$dir}\\{$dir}Module";

            if (class_exists($className)) {
                $module = new $className();
                if ($module instanceof ModuleInterface) {
                    $module->init();
                    $this->modules[$module->getId()] = $module;
                }
            }
        }
    }

    public function getModule(string $id): ?ModuleInterface 
    {
        return $this->modules[$id] ?? null;
    }
}