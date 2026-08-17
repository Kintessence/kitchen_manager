<?php

namespace KitchenManager\Modules\Recipes\Admin;

use KitchenManager\Modules\Recipes\Services\RecipeService;
use KitchenManager\Modules\Ingredients\Services\IngredientService;

class RecipesPage 
{
    private RecipeService $service;
    private IngredientService $ingredientService;

    public function __construct() 
    {
        $this->service = new RecipeService();
        $this->ingredientService = new IngredientService();
    }

    public function registerMenu(): void 
    {
        add_submenu_page(
            'kitchen-manager',
            'Fichas Técnicas & Receitas',
            'Fichas Técnicas',
            'manage_options',
            'kitchen-manager-recipes',
            [$this, 'render'],
            3
        );
    }

    public function handleSaveRecipe(): void 
    {
        $this->handleSave();
    }

    public function handleSave(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        check_admin_referer('km_save_recipe_action', 'km_recipe_nonce');

        $id    = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $name  = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $yield = isset($_POST['yield']) ? (float) str_replace(',', '.', (string)$_POST['yield']) : 1.0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

        $sanitizedItems = [];
        foreach ($items as $it) {
            $ingId = isset($it['ingredient_id']) ? (int) $it['ingredient_id'] : 0;
            $qty   = isset($it['quantity']) ? (float) str_replace(',', '.', (string)$it['quantity']) : 0.0;
            $mType = isset($it['measure_type']) ? sanitize_key($it['measure_type']) : 'unit';

            if ($ingId > 0 && $qty > 0) {
                $sanitizedItems[] = [
                    'ingredient_id' => $ingId,
                    'quantity'      => $qty,
                    'measure_type'  => $mType,
                ];
            }
        }

        try {
            $this->service->saveRecipe($id, $name, $yield, $sanitizedItems, $notes);
            wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-recipes&status=saved'));
            exit;
        } catch (\Throwable $e) {
            wp_die('<strong>Erro ao salvar receita:</strong> ' . esc_html($e->getMessage()) . '<br><br><a href="' . esc_url(admin_url('admin.php?page=kitchen-manager-recipes')) . '">← Voltar</a>');
        }
    }

    public function handleDeleteRecipe(): void 
    {
        $this->handleDelete();
    }

    public function handleDelete(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('km_delete_recipe_' . $id);

        $this->service->deleteRecipe($id);
        wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-recipes&status=deleted'));
        exit;
    }

    public function handleDeleteItem(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        $itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
        check_admin_referer('km_delete_item_' . $itemId);

        if (method_exists($this->service, 'deleteRecipeItem')) {
            $this->service->deleteRecipeItem($itemId);
        }
        
        wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-recipes&status=saved'));
        exit;
    }

    public function render(): void 
    {
        $recipes     = $this->service->getRecipes();
        $ingredients = $this->ingredientService->getIngredients();

        $viewFile = KM_PLUGIN_DIR . 'modules/Recipes/Views/recipes-view.php';
        if (!file_exists($viewFile)) {
            $viewFile = KM_PLUGIN_DIR . 'modules/Recipes/Views/recipe-form.php';
        }

        require_once $viewFile;
    }
}