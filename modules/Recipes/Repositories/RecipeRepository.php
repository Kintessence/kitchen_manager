<?php

namespace KitchenManager\Modules\Recipes\Repositories;

use KitchenManager\Modules\Recipes\DTOs\RecipeDTO;
use KitchenManager\Modules\Recipes\DTOs\RecipeItemDTO;

class RecipeRepository 
{
    private string $tableRecipes;
    private string $tableItems;

    public function __construct() 
    {
        global $wpdb;
        $this->tableRecipes = $wpdb->prefix . 'km_recipes';
        $this->tableItems   = $wpdb->prefix . 'km_recipe_items';
        $this->createTablesIfNotExists();
    }

    public function createTablesIfNotExists(): void 
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sqlRecipes = "CREATE TABLE IF NOT EXISTS {$this->tableRecipes} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            yield_quantity DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
            total_batch_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            unit_portion_cost DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charsetCollate};";

        $sqlItems = "CREATE TABLE IF NOT EXISTS {$this->tableItems} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            ingredient_id BIGINT(20) UNSIGNED NOT NULL,
            quantity DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
            measure_type VARCHAR(20) NOT NULL DEFAULT 'unit',
            cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY recipe_id (recipe_id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sqlRecipes);
        dbDelta($sqlItems);

        // Migração dinâmica garantida para colunas faltantes em km_recipes
        $colsRecipes = $wpdb->get_col("DESC {$this->tableRecipes}", 0);
        if (!in_array('yield_quantity', $colsRecipes)) {
            $wpdb->query("ALTER TABLE {$this->tableRecipes} ADD COLUMN yield_quantity DECIMAL(10, 4) NOT NULL DEFAULT 1.0000 AFTER name");
        }
        if (!in_array('total_batch_cost', $colsRecipes)) {
            $wpdb->query("ALTER TABLE {$this->tableRecipes} ADD COLUMN total_batch_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER yield_quantity");
        }
        if (!in_array('unit_portion_cost', $colsRecipes)) {
            $wpdb->query("ALTER TABLE {$this->tableRecipes} ADD COLUMN unit_portion_cost DECIMAL(10, 4) NOT NULL DEFAULT 0.0000 AFTER total_batch_cost");
        }
        if (!in_array('notes', $colsRecipes)) {
            $wpdb->query("ALTER TABLE {$this->tableRecipes} ADD COLUMN notes TEXT NULL AFTER unit_portion_cost");
        }

        // Migração dinâmica garantida para colunas faltantes em km_recipe_items
        $colsItems = $wpdb->get_col("DESC {$this->tableItems}", 0);
        if (!in_array('measure_type', $colsItems)) {
            $wpdb->query("ALTER TABLE {$this->tableItems} ADD COLUMN measure_type VARCHAR(20) NOT NULL DEFAULT 'unit' AFTER quantity");
        }
        if (!in_array('cost', $colsItems)) {
            $wpdb->query("ALTER TABLE {$this->tableItems} ADD COLUMN cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER measure_type");
        }
    }

    public function getAll(): array 
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->tableRecipes} ORDER BY name ASC");
        
        foreach ($rows as $row) {
            $row->yield = isset($row->yield_quantity) ? (float)$row->yield_quantity : 1.0;
        }

        return $rows;
    }

    public function getById(int $id): ?object 
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tableRecipes} WHERE id = %d", $id));
        if ($row) {
            $row->yield = isset($row->yield_quantity) ? (float)$row->yield_quantity : 1.0;
        }
        return $row ?: null;
    }

    public function getItemsByRecipeId(int $recipeId): array 
    {
        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tableItems} WHERE recipe_id = %d", 
            $recipeId
        ));

        // Normaliza as propriedades dos objetos para a View
        foreach ($items as $it) {
            $it->quantity     = isset($it->quantity) ? (float)$it->quantity : (isset($it->quantity_used) ? (float)$it->quantity_used : 1.0);
            $it->measure_type = $it->measure_type ?? $it->unit_type ?? 'unit';
            $it->cost         = isset($it->cost) ? (float)$it->cost : 0.0;
        }

        return $items;
    }

    public function getItemsByRecipe(int $recipeId): array 
    {
        return $this->getItemsByRecipeId($recipeId);
    }

    public function save(RecipeDTO $dto): int 
    {
        global $wpdb;

        $data = [
            'name'              => $dto->name,
            'yield_quantity'    => $dto->yieldQuantity,
            'total_batch_cost'  => $dto->totalBatchCost,
            'unit_portion_cost' => $dto->unitPortionCost,
            'notes'             => $dto->notes ?? '',
        ];

        if ($dto->id && (int)$dto->id > 0) {
            $wpdb->update($this->tableRecipes, $data, ['id' => (int)$dto->id]);
            return (int) $dto->id;
        } else {
            $wpdb->insert($this->tableRecipes, $data);
            return (int) $wpdb->insert_id;
        }
    }

    public function saveItem($dtoOrArray): bool 
    {
        global $wpdb;

        if (is_object($dtoOrArray)) {
            $recipeId = (int) ($dtoOrArray->recipeId ?? $dtoOrArray->recipe_id ?? 0);
            $ingId    = (int) ($dtoOrArray->ingredientId ?? $dtoOrArray->ingredient_id ?? 0);
            $qty      = (float) ($dtoOrArray->quantity ?? $dtoOrArray->quantityUsed ?? $dtoOrArray->quantity_used ?? 1.0);
            $measure  = (string) ($dtoOrArray->measureType ?? $dtoOrArray->measure_type ?? $dtoOrArray->unit_type ?? 'unit');
            $cost     = (float) ($dtoOrArray->cost ?? $dtoOrArray->totalCost ?? 0.0);
        } else {
            $recipeId = (int) ($dtoOrArray['recipe_id'] ?? 0);
            $ingId    = (int) ($dtoOrArray['ingredient_id'] ?? 0);
            $qty      = (float) ($dtoOrArray['quantity'] ?? 1.0);
            $measure  = sanitize_key($dtoOrArray['measure_type'] ?? 'unit');
            $cost     = (float) ($dtoOrArray['cost'] ?? 0.0);
        }

        if ($recipeId <= 0 || $ingId <= 0) {
            return false;
        }

        // Checa as colunas reais da tabela para montar o array perfeito
        $cols = $wpdb->get_col("DESC {$this->tableItems}", 0);

        $data = [];
        if (in_array('recipe_id', $cols))     $data['recipe_id'] = $recipeId;
        if (in_array('ingredient_id', $cols)) $data['ingredient_id'] = $ingId;

        // Trata divergências de nome na coluna de quantidade
        if (in_array('quantity', $cols)) {
            $data['quantity'] = $qty;
        } elseif (in_array('quantity_used', $cols)) {
            $data['quantity_used'] = $qty;
        }

        // Trata divergências de nome na coluna de medida
        if (in_array('measure_type', $cols)) {
            $data['measure_type'] = $measure;
        } elseif (in_array('unit_type', $cols)) {
            $data['unit_type'] = $measure;
        }

        if (in_array('cost', $cols)) {
            $data['cost'] = $cost;
        }

        $res = $wpdb->insert($this->tableItems, $data);
        if ($res === false) {
            error_log('KM saveItem Error: ' . $wpdb->last_error);
        }

        return (bool) $res;
    }

    public function deleteItemsByRecipeId(int $recipeId): bool 
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->tableItems, ['recipe_id' => $recipeId]);
    }

    public function deleteItem(int $itemId): bool 
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->tableItems, ['id' => $itemId]);
    }

    public function delete(int $id): bool 
    {
        global $wpdb;
        $this->deleteItemsByRecipeId($id);
        return (bool) $wpdb->delete($this->tableRecipes, ['id' => $id]);
    }
}