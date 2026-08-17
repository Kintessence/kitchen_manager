<?php

namespace KitchenManager\Modules\Pricing;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\Pricing\Admin\PricingPage;

class PricingModule implements ModuleInterface 
{
    public function getId(): string 
    {
        return 'pricing';
    }

    public function init(): void 
    {
        $adminPage = new PricingPage();
        add_action('admin_menu', [$adminPage, 'registerMenu']);
    }
}