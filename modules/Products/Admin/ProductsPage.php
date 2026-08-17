<?php

namespace KitchenManager\Modules\Products\Admin;

use KitchenManager\Modules\Products\Services\ProductService;
use KitchenManager\Modules\Recipes\Services\RecipeService;
use KitchenManager\Modules\Ingredients\Services\IngredientService;

class ProductsPage 
{
    private ProductService $service;
    private RecipeService $recipeService;
    private IngredientService $ingredientService;

    public function __construct() 
    {
        $this->service           = new ProductService();
        $this->recipeService     = new RecipeService();
        $this->ingredientService = new IngredientService();
    }

    public function registerMenu(): void 
    {
        add_submenu_page(
            'kitchen-manager',
            'Produtos Comerciais & Kits',
            'Produtos & Kits',
            'manage_options',
            'kitchen-manager-products',
            [$this, 'render'],
            4
        );
    }

    public function handleSave(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        check_admin_referer('km_save_product_action', 'km_product_nonce');

        try {
            $this->service->saveProduct($_POST);
            wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-products&status=saved'));
            exit;
        } catch (\Throwable $e) {
            wp_die('<strong>Erro ao salvar produto:</strong> ' . esc_html($e->getMessage()) . '<br><br><a href="' . esc_url(admin_url('admin.php?page=kitchen-manager-products')) . '">← Voltar</a>');
        }
    }

    public function handleDelete(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('km_delete_product_' . $id);

        try {
            $this->service->deleteProduct($id);
            wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-products&status=deleted'));
            exit;
        } catch (\Throwable $e) {
            wp_die('<strong>Erro ao excluir produto:</strong> ' . esc_html($e->getMessage()));
        }
    }

    public function render(): void 
    {
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'list';

        if ($action === 'new' || $action === 'edit') {
            $recipes     = $this->recipeService->getRecipes();
            $ingredients = $this->ingredientService->getIngredients();
            
            // Garante que cada receita traga o unitCost float limpo para o data-attribute do HTML
            foreach ($recipes as $r) {
                $cost = 0.0;
                if (isset($r->unitCost) && (float)$r->unitCost > 0) {
                    $cost = (float)$r->unitCost;
                } elseif (isset($r->unit_cost) && (float)$r->unit_cost > 0) {
                    $cost = (float)$r->unit_cost;
                } elseif (isset($r->unit_portion_cost) && (float)$r->unit_portion_cost > 0) {
                    $cost = (float)$r->unit_portion_cost;
                } elseif (isset($r->totalCost) && isset($r->yield) && (float)$r->yield > 0) {
                    $cost = (float)$r->totalCost / (float)$r->yield;
                }
                $r->calculated_unit_cost = $cost;
            }

            // Garante que cada insumo traga o unit_cost float limpo para o data-attribute
            foreach ($ingredients as $ing) {
                $pkgSize = isset($ing->package_size) && (float)$ing->package_size > 0 ? (float)$ing->package_size : 1.0;
                $pkgCost = isset($ing->package_cost) ? (float)$ing->package_cost : 0.0;
                $ing->calculated_unit_cost = ($pkgCost / $pkgSize);
            }

            require_once KM_PLUGIN_DIR . 'modules/Products/Views/product-form.php';
        } else {
            $products = $this->service->getProducts();
            $status   = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
            require_once KM_PLUGIN_DIR . 'modules/Products/Views/products-list.php';
        }
    }
}