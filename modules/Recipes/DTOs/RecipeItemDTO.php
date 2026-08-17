<?php

namespace KitchenManager\Modules\Recipes\DTOs;

class RecipeItemDTO 
{
    public function __construct(
        public readonly int $ingredientId,
        public readonly string $ingredientName,
        public readonly float $quantityUsed,
        public readonly string $usageUnit,
        public readonly float $unitCost,
        public readonly float $subtotalCost
    ) {}
}