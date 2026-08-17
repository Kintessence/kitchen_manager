<?php

namespace KitchenManager\Modules\Recipes;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\Recipes\Admin\RecipesPage;

class RecipesModule implements ModuleInterface 
{
    public function getId(): string 
    {
        return 'recipes';
    }

    public function init(): void 
    {
        $page = new RecipesPage();
        
        add_action('admin_menu', [$page, 'registerMenu'], 11);
        add_action('admin_post_km_save_recipe', [$page, 'handleSaveRecipe']);
        add_action('admin_post_km_delete_recipe', [$page, 'handleDeleteRecipe']);
        add_action('admin_post_km_delete_recipe_item', [$page, 'handleDeleteItem']);
    }
}