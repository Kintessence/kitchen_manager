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
        if (is_array($id)) {
            $data  = $id;
            $rId   = !empty($data['id']) ? (int) $data['id'] : null;
            $rName = sanitize_text_field($data['name'] ?? '');
            $rYld  = max(0.1, (float) ($data['yield'] ?? 1.0));
            $rNote = sanitize_textarea_field($data['notes'] ?? '');
            $rItms = $data['items'] ?? [];
        } else {
            $rId   = !empty($id) ? (int) $id : null;
            $rName = sanitize_text_field($name);
            $rYld  = max(0.1, (float) $yield);
            $rNote = sanitize_textarea_field($notes);
            $rItms = is_array($items) ? $items : [];
        }

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

        $totalBatchCost = 0.0;
        $preparedItems = [];

        foreach ($rItms as $it) {
            $ingId = (int) ($it['ingredient_id'] ?? 0);
            $qty   = (float) str_replace(',', '.', (string)($it['quantity'] ?? 0));
            $mType = sanitize_key($it['measure_type'] ?? 'unit');

            if ($ingId > 0 && $qty > 0) {
                $subtotal = 0.0;
                if (isset($ingredients[$ingId])) {
                    $ing = $ingredients[$ingId];
                    if ($mType === 'pkg') {
                        $subtotal = $ing->calculated_pkg_cost * $qty;
                    } elseif ($mType === 'g_from_kg' || $mType === 'ml_from_l') {
                        $subtotal = ($ing->calculated_unit_cost / 1000) * $qty;
                    } else {
                        $subtotal = $ing->calculated_unit_cost * $qty;
                    }
                }
                $totalBatchCost += $subtotal;

                $preparedItems[] = [
                    'ingredient_id' => $ingId,
                    'quantity'      => $qty,
                    'measure_type'  => $mType,
                    'cost'          => $subtotal
                ];
            }
        }

        $unitPortionCost = $rYld > 0 ? ($totalBatchCost / $rYld) : 0.0;

        $recipeDTO = new RecipeDTO(
            $rId,
            $rName,
            (float) $rYld,
            (float) $totalBatchCost,
            (float) $unitPortionCost,
            $preparedItems
        );

        $recipeId = $this->repository->save($recipeDTO);

        global $wpdb;
        $tableItems = $wpdb->prefix . 'km_recipe_items';
        $wpdb->delete($tableItems, ['recipe_id' => $recipeId]);

        foreach ($preparedItems as $it) {
            $this->repository->saveItem([
                'recipe_id'     => $recipeId,
                'ingredient_id' => $it['ingredient_id'],
                'quantity'      => $it['quantity'],
                'measure_type'  => $it['measure_type'],
                'cost'          => $it['cost']
            ]);
        }

        return $recipeId;
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