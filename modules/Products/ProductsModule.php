<?php

namespace KitchenManager\Modules\Products;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\Products\Admin\ProductsPage;

class ProductsModule implements ModuleInterface 
{
    public function getId(): string 
    {
        return 'products';
    }

    public function init(): void 
    {
        $page = new ProductsPage();
        add_action('admin_menu', [$page, 'registerMenu'], 11);
        add_action('admin_post_km_save_product', [$page, 'handleSave']);
        add_action('admin_post_km_delete_product', [$page, 'handleDelete']);
    }
}