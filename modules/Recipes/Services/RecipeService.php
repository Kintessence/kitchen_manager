<?php

namespace KitchenManager\Modules\Recipes\Services;

use KitchenManager\Modules\Recipes\Repositories\RecipeRepository;
use KitchenManager\Modules\Recipes\DTOs\RecipeDTO;
use KitchenManager\Modules\Ingredients\Services\IngredientService;

class RecipeService 
{
    private RecipeRepository $repository;
    private IngredientService $ingredientService;

    public function __construct() 
    {
        $this->repository        = new RecipeRepository();
        $this->ingredientService = new IngredientService();
    }

    public function getRecipes(): array 
    {
        $recipes = $this->repository->getAll();
        $ingredients = [];
        
        foreach ($this->ingredientService->getIngredients() as $ing) {
            $pkgSize = (float)($ing->package_size ?? 1.0);
            $pkgCost = (float)($ing->package_cost ?? 0.0);
            $pkgUnit = $ing->package_unit ?? $ing->unit ?? 'g';
            $unit    = $ing->unit ?? 'g';

            $effectiveSize = $pkgSize;
            if ($pkgUnit === 'kg' && $unit === 'g') {
                $effectiveSize = $pkgSize * 1000;
            } elseif ($pkgUnit === 'l' && $unit === 'ml') {
                $effectiveSize = $pkgSize * 1000;
            }

            $unitCost = ($effectiveSize > 0) ? ($pkgCost / $effectiveSize) : 0.0;
            
            $ing->calculated_unit_cost = $unitCost;
            $ing->calculated_pkg_cost  = $pkgCost;
            $ingredients[$ing->id] = $ing;
        }

        foreach ($recipes as $r) {
            $totalCost = 0.0;
            $items = $this->repository->getItemsByRecipeId((int) $r->id);

            foreach ($items as $it) {
                $ingId = (int) $it->ingredient_id;
                if (isset($ingredients[$ingId])) {
                    $ing = $ingredients[$ingId];
                    $it->name = $ing->name;
                    $it->unit = $ing->unit ?? 'g';
                    $it->package_type = $ing->package_type ?? 'Pacote';

                    $measureType = $it->measure_type ?? 'unit';
                    $qty = (float) $it->quantity;

                    if ($measureType === 'pkg') {
                        $cost = $ing->calculated_pkg_cost * $qty;
                    } elseif ($measureType === 'g_from_kg' || $measureType === 'ml_from_l') {
                        $cost = ($ing->calculated_unit_cost / 1000) * $qty;
                    } else {
                        $cost = $ing->calculated_unit_cost * $qty;
                    }

                    $it->cost = $cost;
                    $totalCost += $cost;
                } else {
                    $it->name = 'Insumo Removido';
                    $it->cost = 0.0;
                    $it->package_type = 'Pacote';
                }
            }

            $r->items     = $items;
            $r->totalCost = $totalCost;
            $yield        = (float) $r->yield > 0 ? (float) $r->yield : 1.0;
            $r->unitCost  = $totalCost / $yield;
        }

        return $recipes;
    }

    public function getRecipe(int $id): ?object 
    {
        $recipes = $this->getRecipes();
        foreach ($recipes as $r) {
            if ($r->id == $id) return $r;
        }
        return null;
    }

    public function saveRecipe($id, $name = '', $yield = 1.0, $items = [], $notes = ''): int 
{
    global $wpdb;

    $rId   = !empty($id) ? (int) $id : null;
    $rName = sanitize_text_field($name);
    $rYld  = max(0.0001, (float) $yield);
    $rNote = sanitize_textarea_field($notes);
    $rItms = is_array($items) ? $items : [];

    // Mapeia todos os insumos para leitura rápida
    $ingredients = [];
    foreach ($this->ingredientService->getIngredients() as $ing) {
        $pkgSize = max(0.0001, (float)($ing->package_size ?? 1.0));
        $pkgCost = (float)($ing->package_cost ?? 0.0);
        $unit    = strtolower((string)($ing->unit ?? 'g'));

        $ing->base_unit_cost = $pkgCost / $pkgSize; // Custo por 1 unidade base cadastrada
        $ing->pkg_cost       = $pkgCost;
        $ing->cad_unit       = $unit;
        $ingredients[$ing->id] = $ing;
    }

    $totalBatchCost = 0.0;
    $preparedItems  = [];

    foreach ($rItms as $it) {
        $ingId = (int) ($it['ingredient_id'] ?? 0);
        $qty   = (float) str_replace(',', '.', (string)($it['quantity'] ?? 0));
        $mType = sanitize_key($it['measure_type'] ?? 'unit');

        if ($ingId > 0 && $qty > 0 && isset($ingredients[$ingId])) {
            $ing = $ingredients[$ingId];
            $subtotal = 0.0;

            if ($mType === 'pkg') {
                // Embalagem Fechada
                $subtotal = $ing->pkg_cost * $qty;
            } elseif ($mType === 'ml_from_l' || ($mType === 'ml' && $ing->cad_unit === 'l')) {
                // Insumo cadastrado em Litros, usado em Mililitros (divide por 1000)
                $subtotal = ($ing->base_unit_cost / 1000) * $qty;
            } elseif ($mType === 'g_from_kg' || ($mType === 'g' && $ing->cad_unit === 'kg')) {
                // Insumo cadastrado em Kg, usado em Gramas (divide por 1000)
                $subtotal = ($ing->base_unit_cost / 1000) * $qty;
            } else {
                // Medida direta (ex: gramas para gramas, litros para litros)
                $subtotal = $ing->base_unit_cost * $qty;
            }

            $totalBatchCost += $subtotal;

            $preparedItems[] = [
                'ingredient_id' => $ingId,
                'quantity'      => $qty,
                'measure_type'  => $mType, // Grava exatamente a medida escolhida
                'cost'          => $subtotal
            ];
        }
    }

    $unitPortionCost = $totalBatchCost / $rYld;

    $recipeDTO = new \KitchenManager\Modules\Recipes\DTOs\RecipeDTO(
        $rId,
        $rName,
        $rYld,
        $totalBatchCost,
        $unitPortionCost,
        $preparedItems,
        $rNote
    );

    // Transação Fail-Safe
    $wpdb->query('START TRANSACTION');

    try {
        $recipeId = $this->repository->save($recipeDTO);
        $this->repository->deleteItemsByRecipeId($recipeId);

        foreach ($preparedItems as $item) {
            $item['recipe_id'] = $recipeId;
            $this->repository->saveItem($item);
        }

        $wpdb->query('COMMIT');
        return $recipeId;
    } catch (\Throwable $e) {
        $wpdb->query('ROLLBACK');
        error_log('KM Persist Error: ' . $e->getMessage());
        throw $e;
    }
}

    public function deleteRecipe(int $id): bool 
    {
        global $wpdb;
        $tableItems = $wpdb->prefix . 'km_recipe_items';
        $wpdb->delete($tableItems, ['recipe_id' => $id]);
        return $this->repository->delete($id);
    }

    public function deleteRecipeItem(int $itemId): bool 
    {
        return $this->repository->deleteItem($itemId);
    }
}