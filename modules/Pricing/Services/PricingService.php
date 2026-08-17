<?php

namespace KitchenManager\Modules\Pricing\Services;

use KitchenManager\Modules\Pricing\Calculators\PricingCalculator;
use KitchenManager\Modules\Pricing\DTOs\PricingAnalysisDTO;

class PricingService 
{
    private PricingCalculator $calculator;

    public function __construct() 
    {
        $this->calculator = new PricingCalculator();
    }

    public function analyzePricing(
        float $directCost, 
        float $sellingPrice, 
        float $taxPercentage, 
        float $minTargetMargin = 20.0
    ): PricingAnalysisDTO {
        $taxAmount = round($sellingPrice * ($taxPercentage / 100), 2);
        $netProfitAmount = round($sellingPrice - $directCost - $taxAmount, 2);
        $realMargin = $this->calculator->calculateRealMargin($sellingPrice, $directCost, $taxPercentage);
        $multiplier = $this->calculator->calculateMultiplier($sellingPrice, $directCost);

        return new PricingAnalysisDTO(
            directCost: $directCost,
            sellingPrice: $sellingPrice,
            taxPercentage: $taxPercentage,
            taxAmount: $taxAmount,
            netProfitAmount: $netProfitAmount,
            realMarginPercentage: $realMargin,
            markupMultiplier: $multiplier,
            isMarginHealthy: $realMargin >= $minTargetMargin
        );
    }

    public function getSuggestedPrice(float $directCost, float $taxPercentage, float $targetMargin): float 
    {
        return $this->calculator->calculateSuggestedPrice($directCost, $taxPercentage, $targetMargin);
    }
}