<?php

namespace KitchenManager\Modules\Recipes\DTOs;

class RecipeDTO 
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly float $yieldQuantity,
        public readonly float $totalBatchCost,
        public readonly float $unitPortionCost,
        public readonly array $items = [],
        public readonly ?string $notes = ''
    ) {}
}