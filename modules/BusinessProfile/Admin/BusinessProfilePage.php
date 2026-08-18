<?php

declare(strict_types=1);

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
            'Perfil Financeiro | Kitchen Manager',
            '⚙️ Perfil Financeiro',
            'manage_options',
            'kitchen-manager-settings',
            [$this, 'renderPage']
        );
    }

    public function handleSaveProfile(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado.');
        }

        check_admin_referer('km_save_profile_nonce', 'km_profile_nonce');

        try {
            $this->service->saveProfileFromInput($_POST);
            wp_safe_redirect(admin_url('admin.php?page=kitchen-manager-settings&status=saved'));
            exit;
        } catch (\Throwable $e) {
            error_log('KM Profile Save Error: ' . $e->getMessage());
            wp_die('Erro ao salvar parâmetros: ' . esc_html($e->getMessage()));
        }
    }

    public function renderPage(): void
    {
        $profile = $this->service->getProfile();
        $metrics = $this->service->calculateMetrics($profile);
        $status  = sanitize_key($_GET['status'] ?? '');

        require_once dirname(__DIR__) . '/Views/onboarding-wizard.php';
    }
}