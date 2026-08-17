<?php

namespace KitchenManager\Modules\Dashboard;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\Dashboard\Admin\DashboardPage;

class DashboardModule implements ModuleInterface 
{
    public function getId(): string 
    {
        return 'dashboard';
    }

    public function init(): void 
    {
        $page = new DashboardPage();
        add_action('admin_menu', [$page, 'registerMenu'], 5);
    }
}