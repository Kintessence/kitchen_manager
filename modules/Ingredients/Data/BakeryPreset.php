<?php

namespace KitchenManager\Modules\Ingredients\Data;

class BakeryPreset 
{
    public static function getItems(): array 
    {
        return [
            [
                'name' => 'Leite Condensado (Moça/Itambé)',
                'purchase_quantity' => 1,
                'purchase_unit' => 'un',
                'net_weight' => 395,
                'purchase_price' => 8.50,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Creme de Leite (Caixinha)',
                'purchase_quantity' => 1,
                'purchase_unit' => 'un',
                'net_weight' => 200,
                'purchase_price' => 3.80,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Cacau em Pó 100% (Sicao/Callebaut)',
                'purchase_quantity' => 1,
                'purchase_unit' => 'kg',
                'net_weight' => 0,
                'purchase_price' => 58.00,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Chocolate Nobre Meio Amargo',
                'purchase_quantity' => 1.05,
                'purchase_unit' => 'kg',
                'net_weight' => 0,
                'purchase_price' => 62.00,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Manteiga sem Sal (Tablete)',
                'purchase_quantity' => 1,
                'purchase_unit' => 'un',
                'net_weight' => 200,
                'purchase_price' => 12.50,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Farinha de Trigo Tradicional',
                'purchase_quantity' => 1,
                'purchase_unit' => 'kg',
                'net_weight' => 0,
                'purchase_price' => 5.20,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Açúcar Refinado Especial',
                'purchase_quantity' => 1,
                'purchase_unit' => 'kg',
                'net_weight' => 0,
                'purchase_price' => 4.80,
                'usage_unit' => 'g'
            ],
            [
                'name' => 'Ovos Médios/Grandes (Placa c/ 30)',
                'purchase_quantity' => 30,
                'purchase_unit' => 'un',
                'net_weight' => 0,
                'purchase_price' => 24.00,
                'usage_unit' => 'un'
            ],
            [
                'name' => 'Forminhas nº 4 / 5 Brigadeiro (Cento)',
                'purchase_quantity' => 100,
                'purchase_unit' => 'un',
                'net_weight' => 0,
                'purchase_price' => 7.00,
                'usage_unit' => 'un'
            ],
            [
                'name' => 'Embalagem Tampa Articulada Individual',
                'purchase_quantity' => 100,
                'purchase_unit' => 'un',
                'net_weight' => 0,
                'purchase_price' => 45.00,
                'usage_unit' => 'un'
            ]
        ];
    }
}