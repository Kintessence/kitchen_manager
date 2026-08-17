<?php

namespace KitchenManager\Modules\Ingredients\Services;

use KitchenManager\Modules\Ingredients\Repositories\IngredientRepository;

class IngredientService 
{
    private IngredientRepository $repository;

    public const CATEGORIES = [
        'food'       => '🍎 Alimento / Matéria-Prima',
        'packaging'  => '📦 Embalagem & Descartável',
        'decoration' => '🌸 Decoração & Flores',
        'accessory'  => '🎀 Acessório & Fitas / Tags',
        'other'      => '🏷️ Outros Insumos'
    ];

    public function __construct() 
    {
        $this->repository = new IngredientRepository();
    }

    public function getIngredients(): array 
    {
        return $this->repository->getAll();
    }

    public function getNonFoodIngredients(): array 
    {
        global $wpdb;
        $table = $wpdb->prefix . 'km_ingredients';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE category != %s ORDER BY category ASC, name ASC", 'food')
        );
    }

    public function getCategories(): array 
    {
        return self::CATEGORIES;
    }

    public function saveBatch(array $items): bool 
    {
        return $this->repository->saveBatch($items);
    }

    public function deleteIngredient(int $id): bool 
    {
        return $this->repository->delete($id);
    }
}