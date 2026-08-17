<?php

namespace KitchenManager\Modules\BusinessProfile\Admin;

use KitchenManager\Modules\BusinessProfile\Services\BusinessProfileService;

class BusinessProfilePage 
{
    private BusinessProfileService $service;

    public function __construct() 
    {
        $this->service = new BusinessProfileService();
    }

    public function registerMenu(): void 
    {
        add_submenu_page(
            'kitchen-manager',
            'Perfil do Negócio & Custos Estruturais',
            'Perfil do Negócio',
            'manage_options',
            'kitchen-manager-business-profile',
            [$this, 'render'],
            12
        );
    }

    public function handleSave(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        check_admin_referer('km_save_profile_action', 'km_profile_nonce');

        $this->service->saveProfile($_POST);
        wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-business-profile&status=saved'));
        exit;
    }

    public function handleReset(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso não autorizado.');
        }

        check_admin_referer('km_reset_profile_action');

        $this->service->resetToDefaults();
        wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-business-profile&status=reset'));
        exit;
    }

    public function render(): void 
    {
        $profile = $this->service->getProfile();
        $totals  = $this->service->calculateTotals($profile);
        $status  = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        require_once KM_PLUGIN_DIR . 'modules/BusinessProfile/Views/business-profile-view.php';
    }
}