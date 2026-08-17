<?php

namespace KitchenManager\Modules\Products\Services;

use KitchenManager\Modules\Products\Repositories\ProductRepository;
use KitchenManager\Modules\Recipes\Services\RecipeService;
use KitchenManager\Modules\Ingredients\Services\IngredientService;

class ProductService 
{
    private ProductRepository $repository;
    private ?RecipeService $recipeService = null;
    private ?IngredientService $ingredientService = null;

    public const ROLES = [
        'lead' => [
            'label'            => '🎯 Produto Isca / Giro Rápido',
            'badge_class'      => 'km-badge-lead',
            'suggested_margin' => 20.0,
            'desc'             => 'Margem menor (15-25%). Usado para atrair clientes, gerar volume e puxar a venda de outros produtos mais lucrativos.'
        ],
        'anchor' => [
            'label'            => '⭐ Carro-Chefe / Âncora',
            'badge_class'      => 'km-badge-anchor',
            'suggested_margin' => 30.0,
            'desc'             => 'Margem padrão equilibrada (25-35%). O produto mais consistente da casa, responsável por sustentar os custos fixos mensais.'
        ],
        'premium' => [
            'label'            => '💎 Premium / Encomenda',
            'badge_class'      => 'km-badge-premium',
            'suggested_margin' => 45.0,
            'desc'             => 'Margem alta (40-60%+). Produtos exclusivos, personalizados, kits para presente ou festas com alta percepção de valor.'
        ],
        'addon' => [
            'label'            => '🎁 Agregador / Combo',
            'badge_class'      => 'km-badge-addon',
            'suggested_margin' => 35.0,
            'desc'             => 'Margem de conveniência (30-50%). Embalagens luxuosas, flores, velas e complementos adicionados ao pedido principal.'
        ]
    ];

    public function __construct() 
    {
        $this->repository = new ProductRepository();
        
        if (class_exists(RecipeService::class)) {
            $this->recipeService = new RecipeService();
        }
        if (class_exists(IngredientService::class)) {
            $this->ingredientService = new IngredientService();
        }
    }

    public function getProducts(): array 
    {
        $products = $this->repository->getAll();
        
        foreach ($products as $p) {
            $full = $this->getProduct((int) $p->id);
            $p->total_cost     = $full->total_cost ?? 0.0;
            $p->role_data      = $full->role_data;
            $p->current_margin = $full->current_margin ?? 0.0;
            $p->composition_summary = $full->composition_summary ?? '';
        }

        return $products;
    }

    public function getProduct(int $id): ?object 
    {
        $p = $this->repository->getById($id);
        if (!$p) return null;

        $recipes = [];
        if ($this->recipeService) {
            foreach ($this->recipeService->getRecipes() as $r) {
                $rId = is_object($r) ? ($r->id ?? 0) : ($r['id'] ?? 0);
                $recipes[$rId] = $r;
            }
        }

        $ingredients = [];
        if ($this->ingredientService) {
            foreach ($this->ingredientService->getIngredients() as $ing) {
                $pkgSize = isset($ing->package_size) ? (float) $ing->package_size : 1.0;
                $pkgCost = isset($ing->package_cost) ? (float) $ing->package_cost : 0.0;
                $unitCost = ($pkgSize > 0) ? ($pkgCost / $pkgSize) : 0;
                $ing->unit_cost = $unitCost;
                $ingredients[$ing->id] = $ing;
            }
        }

        $totalCost = 0.0;
        $summaryParts = [];

        if (!empty($p->items)) {
            foreach ($p->items as $item) {
                if ($item->item_type === 'recipe' && isset($recipes[$item->item_id])) {
                    $rec = $recipes[$item->item_id];
                    $uCost = is_object($rec) ? ($rec->unitCost ?? $rec->unit_cost ?? 0.0) : ($rec['unitCost'] ?? 0.0);
                    $rName = is_object($rec) ? ($rec->name ?? 'Receita') : ($rec['name'] ?? 'Receita');
                    
                    $cost = $uCost * (float) $item->quantity;
                    $item->unit_cost  = $uCost;
                    $item->total_cost = $cost;
                    $item->name       = $rName;
                    $totalCost       += $cost;
                    $summaryParts[]   = "{$rName} (" . (float)$item->quantity . "x)";
                } elseif ($item->item_type === 'ingredient' && isset($ingredients[$item->item_id])) {
                    $ing = $ingredients[$item->item_id];
                    $cost = $ing->unit_cost * (float) $item->quantity;
                    $item->unit_cost  = $ing->unit_cost;
                    $item->total_cost = $cost;
                    $item->name       = $ing->name;
                    $item->unit       = $ing->unit ?? 'g';
                    $totalCost       += $cost;
                    $summaryParts[]   = "{$ing->name} (" . (float)$item->quantity . " {$item->unit})";
                }
            }
        }

        $p->total_cost = $totalCost;
        $p->role_data  = self::ROLES[$p->strategic_role] ?? self::ROLES['anchor'];
        $p->composition_summary = implode(', ', $summaryParts);

        $salePrice = (float) $p->sale_price;
        $p->current_margin = ($salePrice > 0) ? ((($salePrice - $totalCost) / $salePrice) * 100) : 0.0;

        return $p;
    }

    public function saveProduct(array $postData): int 
    {
        $items = [];

        if (!empty($postData['items']) && is_array($postData['items'])) {
            foreach ($postData['items'] as $it) {
                $items[] = [
                    'item_type'  => sanitize_key($it['item_type'] ?? 'recipe'),
                    'item_id'    => (int) ($it['item_id'] ?? 0),
                    'quantity'   => (float) str_replace(',', '.', (string)($it['quantity'] ?? 1)),
                    'group_type' => sanitize_key($it['group_type'] ?? 'fixed'),
                ];
            }
        }

        return $this->repository->save($postData, $items);
    }

    public function deleteProduct(int $id): bool 
    {
        return $this->repository->delete($id);
    }
}