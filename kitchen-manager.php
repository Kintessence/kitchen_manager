<?php
/**
 * Plugin Name: Kitchen Manager (Gestão Gastronômica)
 * Plugin URI:  https://estudiodrquem.com.br
 * Description: Sistema modular de precificação, controle de insumos, fichas técnicas e inteligência financeira para confeitarias e cozinhas artesanais.
 * Version:     1.0.0
 * Author:      Estúdio Dr.Quem!
 * Author URI:  https://estudiodrquem.com.br
 * Text Domain: kitchen-manager
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Constantes Globais
define('KM_VERSION', '1.1.0');
define('KM_PLUGIN_FILE', __FILE__);
define('KM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KM_PLUGIN_URL', plugin_dir_url(__FILE__));

// 2. Autoloader PSR-4
require_once KM_PLUGIN_DIR . 'core/Autoloader.php';
\KitchenManager\Core\Autoloader::register();

// 3. Inicialização do Kernel e Descoberta Automática de Módulos
add_action('plugins_loaded', function () {
    $plugin = new \KitchenManager\Core\Plugin();
    $plugin->init();
});

// 4. Hooks de Ativação / Desativação
register_activation_hook(__FILE__, function () {
    // Garante que tabelas de insumos e fichas técnicas existam ao ativar
    if (class_exists('\KitchenManager\Modules\Ingredients\Repositories\IngredientRepository')) {
        new \KitchenManager\Modules\Ingredients\Repositories\IngredientRepository();
    }
    if (class_exists('\KitchenManager\Modules\Recipes\Repositories\RecipeRepository')) {
        new \KitchenManager\Modules\Recipes\Repositories\RecipeRepository();
    }
});