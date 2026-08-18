<?php

namespace KitchenManager\Modules\Ingredients\Services;

use KitchenManager\Modules\Ingredients\Repositories\IngredientRepository;

class IngredientService 
{
    private IngredientRepository $repository;

    public const CATEGORIES = [
        'food'       => '🍎 Alimento / Matéria-Prima',
        'packaging'  => '📦 Embalagem & Descartável',
        'decoration' => '🌸 Decoração & Flores',
        'accessory'  => '🎀 Acessório & Fitas / Tags',
        'other'      => '🏷️ Outros Insumos'
    ];

    public function __construct() 
    {
        $this->repository = new IngredientRepository();
    }

    public function getIngredients(string $orderby = 'category', string $order = 'ASC'): array 
    {
        return $this->repository->getAll($orderby, $order);
    }

    public function getAllIngredients(string $orderby = 'category', string $order = 'ASC'): array 
    {
        return $this->getIngredients($orderby, $order);
    }

    public function getNonFoodIngredients(): array 
    {
        global $wpdb;
        $table = $wpdb->prefix . 'km_ingredients';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE category != %s ORDER BY category ASC, name ASC", 'food')
        );
    }

    public function getCategories(): array 
    {
        return self::CATEGORIES;
    }

    public function saveBatch(array $items): bool 
    {
        return $this->repository->saveBatch($items);
    }

    public function deleteIngredient(int $id): bool 
    {
        return $this->repository->delete($id);
    }

    public function clearAllIngredients(): bool 
    {
        global $wpdb;
        $table = $wpdb->prefix . 'km_ingredients';
        return (bool) $wpdb->query("TRUNCATE TABLE {$table}");
    }

    /**
     * Processa o texto ou CSV colado pelo usuário e divide em linhas/colunas normalizadas
     */
    public function parseRawTextOrCsv(string $raw_text): array 
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw_text));
        $parsed_data = [];

        foreach ($lines as $index => $line) {
            if (trim($line) === '') continue;

            // Auto-detecta delimitador: Tabulação (\t), Ponto e Vírgula (;) ou Vírgula (,)
            if (strpos($line, "\t") !== false) {
                $cols = explode("\t", $line);
            } elseif (strpos($line, ';') !== false) {
                $cols = str_getcsv($line, ';');
            } else {
                $cols = str_getcsv($line, ',');
            }

            $cols = array_map('trim', $cols);

            // Se for a linha de cabeçalho, pula
            if ($index === 0 && (stripos($cols[0], 'nome') !== false || stripos($cols[0], 'insumo') !== false)) {
                continue;
            }

            // Mapeamento: 0: Nome, 1: Categoria, 2: Preço Pacote, 3: Qtd Pacote, 4: Unidade
            if (!empty($cols[0])) {
                $categoryInput = strtolower($cols[1] ?? 'food');
                $matchedCategory = 'food';

                // Mapeamento tolerante de categoria digitada em português
                if (str_contains($categoryInput, 'embalag') || str_contains($categoryInput, 'pack')) {
                    $matchedCategory = 'packaging';
                } elseif (str_contains($categoryInput, 'decor') || str_contains($categoryInput, 'flor')) {
                    $matchedCategory = 'decoration';
                } elseif (str_contains($categoryInput, 'fita') || str_contains($categoryInput, 'tag') || str_contains($categoryInput, 'acess')) {
                    $matchedCategory = 'accessory';
                } elseif (str_contains($categoryInput, 'outro')) {
                    $matchedCategory = 'other';
                }

                $parsed_data[] = [
                    'name'         => $cols[0] ?? '',
                    'category'     => $matchedCategory,
                    'package_cost' => $cols[2] ?? '0.00',
                    'package_size' => $cols[3] ?? '1.0000',
                    'unit'         => !empty($cols[4]) ? strtolower($cols[4]) : 'g',
                ];
            }
        }

        return $parsed_data;
    }

    /**
     * Salva os itens importados usando a persistência em lote segura
     */
    public function importBulk(array $rows): int 
    {
        if (empty($rows)) {
            return 0;
        }

        $this->saveBatch($rows);
        return count($rows);
    }
}