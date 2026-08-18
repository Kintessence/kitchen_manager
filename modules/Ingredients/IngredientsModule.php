<?php

namespace KitchenManager\Modules\Ingredients;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\Ingredients\Admin\IngredientsPage;

class IngredientsModule implements ModuleInterface 
{
    public function getId(): string 
    {
        return 'ingredients';
    }

    public function init(): void 
    {
        $page = new IngredientsPage();
        
        add_action('admin_menu', [$page, 'registerMenu'], 10);
        add_action('admin_post_km_save_ingredients', [$page, 'handleSave']);
        add_action('admin_post_km_delete_ingredient', [$page, 'handleDelete']);
        add_action('admin_post_km_import_ingredients', [$page, 'handleImport']);
    }

    
}