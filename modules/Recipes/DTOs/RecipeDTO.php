<?php

namespace KitchenManager\Modules\Recipes\DTOs;

class RecipeDTO 
{
    /**
     * @param RecipeItemDTO[] $items
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly float $yieldQuantity, // Rendimento em porções/unidades
        public readonly float $totalBatchCost, // Custo total da fornada/receita
        public readonly float $unitCost, // Custo por porção
        public readonly array $items = []
    ) {}
}