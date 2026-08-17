<?php

namespace KitchenManager\Modules\Dashboard\Admin;

class DashboardPage 
{
    public function registerMenu(): void 
    {
        // Cria o menu raiz "Minha Cozinha"
        add_menu_page(
            'Kitchen Manager',
            'Minha Cozinha',
            'manage_options',
            'kitchen-manager',
            [$this, 'render'],
            'dashicons-food',
            25
        );

        // Submenu correspondente à tela inicial
        add_submenu_page(
            'kitchen-manager',
            'Minha Cozinha — Painel Geral',
            'Minha Cozinha',
            'manage_options',
            'kitchen-manager',
            [$this, 'render'],
            1
        );
    }

    public function render(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        // Renderiza a view diretamente sem nenhum redirect
        require_once KM_PLUGIN_DIR . 'modules/Dashboard/Views/dashboard.php';
    }
}