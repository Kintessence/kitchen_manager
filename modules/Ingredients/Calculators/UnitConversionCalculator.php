<?php

namespace KitchenManager\Modules\Ingredients\Calculators;

class UnitConversionCalculator 
{
    private const CONVERSION_RATES = [
        'kg_to_g'  => 1000.0,
        'g_to_g'   => 1.0,
        'l_to_ml'  => 1000.0,
        'ml_to_ml' => 1.0,
        'un_to_un' => 1.0,
    ];

    private const PACKAGE_UNITS = ['un', 'lata', 'cx', 'pote', 'pct'];

    public function getConversionFactor(string $purchaseUnit, string $usageUnit, float $packageNetWeight = 1.0): float 
    {
        $pUnit = strtolower(trim($purchaseUnit));
        $uUnit = strtolower(trim($usageUnit));

        // Se comprou por embalagem unitária (lata, caixa, pote, pacote, un) e usa em g ou ml
        if (in_array($pUnit, self::PACKAGE_UNITS, true)) {
            if (in_array($uUnit, ['g', 'ml'], true)) {
                return $packageNetWeight > 0 ? $packageNetWeight : 1.0;
            }
            // Se usa em unidade inteira (ex: 1 lata usada como 1 unidade)
            return 1.0;
        }

        $key = "{$pUnit}_to_{$uUnit}";
        if (isset(self::CONVERSION_RATES[$key])) {
            return self::CONVERSION_RATES[$key];
        }

        if ($pUnit === $uUnit) {
            return 1.0;
        }

        return 1.0;
    }

    public function calculateUnitUseCost(float $purchasePrice, float $purchaseQuantity, float $conversionFactor): float 
    {
        $totalBaseUnits = $purchaseQuantity * $conversionFactor;
        if ($totalBaseUnits <= 0.0) {
            return 0.0;
        }

        return round($purchasePrice / $totalBaseUnits, 6);
    }
}