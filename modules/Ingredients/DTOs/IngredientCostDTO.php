<?php

namespace KitchenManager\Modules\Ingredients\DTOs;

class IngredientCostDTO 
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly float $purchaseQuantity,
        public readonly string $purchaseUnit,
        public readonly float $purchasePrice,
        public readonly string $usageUnit,
        public readonly float $conversionFactor,
        public readonly float $unitUseCost
    ) {}

    public function toArray(): array 
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'purchase_quantity' => $this->purchaseQuantity,
            'purchase_unit' => $this->purchaseUnit,
            'purchase_price' => $this->purchasePrice,
            'usage_unit' => $this->usageUnit,
            'conversion_factor' => $this->conversionFactor,
            'unit_use_cost' => $this->unitUseCost,
        ];
    }
}