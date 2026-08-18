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
    $orderby = sanitize_key($_GET['orderby'] ?? 'name');
    $order   = strtoupper(sanitize_key($_GET['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

    $ingredients = $this->service->getAllIngredients($orderby, $order);
    $status      = sanitize_key($_GET['status'] ?? '');

    include KM_PLUGIN_DIR . 'modules/Ingredients/Views/ingredients-list.php';
    }

    public function handleImport(): void 
{
    if (!current_user_can('manage_options')) {
        wp_die('Acesso não autorizado.');
    }

    check_admin_referer('km_import_ingredients_nonce');

    $raw_content = '';

    // 1. Caso venha de upload de arquivo .CSV
    if (!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $raw_content = file_get_contents($_FILES['csv_file']['tmp_name']);
    } 
    // 2. Caso venha de texto colado no textarea
    elseif (!empty($_POST['raw_textarea'])) {
        $raw_content = wp_unslash($_POST['raw_textarea']);
    }

    if (empty(trim($raw_content))) {
        wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-ingredients&status=import_empty'));
        exit;
    }

    try {
        $rows  = $this->service->parseRawTextOrCsv($raw_content);
        $count = $this->service->importBulk($rows);

        wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-ingredients&status=imported&count=' . $count));
        exit;
    } catch (\Throwable $e) {
        wp_die('<strong>Erro na importação:</strong> ' . esc_html($e->getMessage()));
    }
}
}