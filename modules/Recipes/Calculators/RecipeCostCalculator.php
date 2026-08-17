<?php

namespace KitchenManager\Modules\Recipes\Calculators;

class RecipeCostCalculator 
{
    public function calculateItemSubtotal(float $quantityUsed, float $unitCost): float 
    {
        return round($quantityUsed * $unitCost, 4);
    }

    public function calculateUnitPortionCost(float $totalBatchCost, float $yieldQuantity): float 
    {
        if ($yieldQuantity <= 0) {
            return 0.0;
        }
        return round($totalBatchCost / $yieldQuantity, 2);
    }
}