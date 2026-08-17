<?php

namespace KitchenManager\Modules\Pricing\Calculators;

class PricingCalculator 
{
    public function calculateSuggestedPrice(float $directCost, float $taxPercentage, float $targetMarginPercentage): float 
    {
        $totalDeductions = $taxPercentage + $targetMarginPercentage;
        if ($totalDeductions >= 100.0) {
            throw new \InvalidArgumentException('A soma de taxas e margem não pode atingir ou superar 100%.');
        }

        if ($directCost <= 0) {
            return 0.0;
        }

        $divisor = 1 - ($totalDeductions / 100);
        return round($directCost / $divisor, 2);
    }

    public function calculateRealMargin(float $sellingPrice, float $directCost, float $taxPercentage): float 
    {
        if ($sellingPrice <= 0) {
            return 0.0;
        }

        $taxAmount = $sellingPrice * ($taxPercentage / 100);
        $netProfitAmount = $sellingPrice - $directCost - $taxAmount;
        return round(($netProfitAmount / $sellingPrice) * 100, 2);
    }

    public function calculateMultiplier(float $sellingPrice, float $directCost): float 
    {
        if ($directCost <= 0) {
            return 0.0;
        }
        return round($sellingPrice / $directCost, 2);
    }
}