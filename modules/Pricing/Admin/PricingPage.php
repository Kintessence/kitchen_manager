<?php

namespace KitchenManager\Modules\Pricing\Admin;

use KitchenManager\Modules\Pricing\Services\PricingService;

class PricingPage 
{
    private PricingService $service;

    public function __construct() 
    {
        $this->service = new PricingService();
    }

    public function registerMenu(): void 
    {
        add_submenu_page(
            'kitchen-manager',
            'Simulador de Precificação',
            'Precificação',
            'manage_options',
            'kitchen-manager-pricing',
            [$this, 'render'],
            10
        );
    }

    public function render(): void 
    {
        $service = $this->service;
        require_once KM_PLUGIN_DIR . 'modules/Pricing/Views/pricing-simulator.php';
    }
}