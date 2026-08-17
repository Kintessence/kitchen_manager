<?php

namespace KitchenManager\Modules\BusinessProfile;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\BusinessProfile\Admin\BusinessProfilePage;

class BusinessProfileModule implements ModuleInterface 
{
    public function getId(): string 
    {
        return 'business_profile';
    }

    public function init(): void 
    {
        $page = new BusinessProfilePage();
        add_action('admin_menu', [$page, 'registerMenu'], 12);
        add_action('admin_post_km_save_business_profile', [$page, 'handleSave']);
        add_action('admin_post_km_reset_business_profile', [$page, 'handleReset']);
    }
}