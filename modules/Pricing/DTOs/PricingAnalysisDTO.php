<?php

namespace KitchenManager\Modules\Pricing\DTOs;

class PricingAnalysisDTO 
{
    public function __construct(
        public readonly float $directCost,
        public readonly float $sellingPrice,
        public readonly float $taxPercentage,
        public readonly float $taxAmount,
        public readonly float $netProfitAmount,
        public readonly float $realMarginPercentage,
        public readonly float $markupMultiplier,
        public readonly bool $isMarginHealthy
    ) {}

    public function toArray(): array 
    {
        return [
            'direct_cost' => $this->directCost,
            'selling_price' => $this->sellingPrice,
            'tax_percentage' => $this->taxPercentage,
            'tax_amount' => $this->taxAmount,
            'net_profit_amount' => $this->netProfitAmount,
            'real_margin_percentage' => $this->realMarginPercentage,
            'markup_multiplier' => $this->markupMultiplier,
            'is_margin_healthy' => $this->isMarginHealthy,
        ];
    }
}