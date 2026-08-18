<?php

declare(strict_types=1);

namespace KitchenManager\Modules\BusinessProfile;

use KitchenManager\Core\ModuleInterface;
use KitchenManager\Modules\BusinessProfile\Admin\BusinessProfilePage;

class BusinessProfileModule implements ModuleInterface
{
    /**
     * Identificador único do módulo exigido pela ModuleInterface
     */
    public function getId(): string
    {
        return 'business_profile';
    }

    /**
     * Nome amigável do módulo
     */
    public function getName(): string
    {
        return 'Perfil Financeiro';
    }

    /**
     * Ordem de carregamento (opcional, se exigido pelo contrato)
     */
    public function getOrder(): int
    {
        return 5;
    }

    /**
     * Inicialização de hooks do WordPress
     */
    public function init(): void
    {
        $page = new BusinessProfilePage();
        
        add_action('admin_menu', [$page, 'registerMenu'], 9);
        add_action('admin_post_km_save_profile', [$page, 'handleSaveProfile']);
    }
}