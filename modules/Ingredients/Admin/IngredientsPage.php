<?php

namespace KitchenManager\Modules\Ingredients\Admin;

use KitchenManager\Modules\Ingredients\Services\IngredientService;

class IngredientsPage 
{
    private IngredientService $service;

    public function __construct() 
    {
        $this->service = new IngredientService();
    }

    public function registerMenu(): void 
    {
        add_submenu_page(
            'kitchen-manager',
            'Cadastro de Insumos & Custos',
            'Insumos & Custos',
            'manage_options',
            'kitchen-manager-ingredients',
            [$this, 'render'],
            2
        );
    }

    public function handleSave(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado ao módulo de insumos.');
        }

        check_admin_referer('km_save_ingredients_action', 'km_ingredients_nonce');

        $items = isset($_POST['ingredients']) && is_array($_POST['ingredients']) ? $_POST['ingredients'] : [];

        try {
            $this->service->saveBatch($items);
            wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-ingredients&status=saved'));
            exit;
        } catch (\Throwable $e) {
            wp_die('<strong>Erro ao salvar insumos:</strong> ' . esc_html($e->getMessage()) . '<br><br><a href="' . esc_url(admin_url('admin.php?page=kitchen-manager-ingredients')) . '">← Voltar</a>');
        }
    }

    public function handleDelete(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('km_delete_ingredient_' . $id);

        try {
            $this->service->deleteIngredient($id);
            wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-ingredients&status=deleted'));
            exit;
        } catch (\Throwable $e) {
            wp_die('<strong>Erro ao excluir insumo:</strong> ' . esc_html($e->getMessage()));
        }
    }

    public function render(): void 
    {
        $ingredients = $this->service->getIngredients();
        $status      = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

        require_once KM_PLUGIN_DIR . 'modules/Ingredients/Views/ingredients-list.php';
    }
}