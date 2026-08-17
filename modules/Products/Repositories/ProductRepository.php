<?php

namespace KitchenManager\Modules\Products\Repositories;

class ProductRepository 
{
    private string $tableProducts;
    private string $tableItems;

    public function __construct() 
    {
        global $wpdb;
        $this->tableProducts = $wpdb->prefix . 'km_products';
        $this->tableItems    = $wpdb->prefix . 'km_product_items';
        $this->createTablesIfNotExists();
    }

    public function createTablesIfNotExists(): void 
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sqlProducts = "CREATE TABLE IF NOT EXISTS {$this->tableProducts} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            strategic_role VARCHAR(50) NOT NULL DEFAULT 'anchor',
            sale_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            target_margin DECIMAL(5, 2) NOT NULL DEFAULT 30.00,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charsetCollate};";

        $sqlItems = "CREATE TABLE IF NOT EXISTS {$this->tableItems} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            item_type VARCHAR(20) NOT NULL, -- 'recipe' ou 'ingredient'
            item_id BIGINT(20) UNSIGNED NOT NULL,
            quantity DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
            group_type VARCHAR(50) NOT NULL DEFAULT 'fixed', -- 'fixed' ou 'modifier'
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sqlProducts);
        dbDelta($sqlItems);
    }

    public function getAll(): array 
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tableProducts} ORDER BY name ASC")
        );
    }

    public function getById(int $id): ?object 
    {
        global $wpdb;
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tableProducts} WHERE id = %d", $id));
        if (!$product) return null;

        // Busca itens/fichas vinculadas
        $product->items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tableItems} WHERE product_id = %d", $id
        ));

        return $product;
    }

    public function save(array $data, array $items): int 
    {
        global $wpdb;

        $fields = [
            'name'           => sanitize_text_field($data['name'] ?? ''),
            'strategic_role' => sanitize_key($data['strategic_role'] ?? 'anchor'),
            'sale_price'     => max(0, (float) ($data['sale_price'] ?? 0.0)),
            'target_margin'  => max(0, min(90, (float) ($data['target_margin'] ?? 30.0))),
            'notes'          => sanitize_textarea_field($data['notes'] ?? ''),
        ];

        $id = (int) ($data['id'] ?? 0);

        if ($id > 0) {
            $wpdb->update($this->tableProducts, $fields, ['id' => $id]);
        } else {
            $wpdb->insert($this->tableProducts, $fields);
            $id = (int) $wpdb->insert_id;
        }

        // Atualiza Itens / Composição
        $wpdb->delete($this->tableItems, ['product_id' => $id]);

        foreach ($items as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $qty    = (float) ($item['quantity'] ?? 0);
            $type   = sanitize_key($item['item_type'] ?? 'recipe');
            $group  = sanitize_key($item['group_type'] ?? 'fixed');

            if ($itemId > 0 && $qty > 0) {
                $wpdb->insert($this->tableItems, [
                    'product_id' => $id,
                    'item_type'  => $type,
                    'item_id'    => $itemId,
                    'quantity'   => $qty,
                    'group_type' => $group,
                ]);
            }
        }

        return $id;
    }

    public function delete(int $id): bool 
    {
        global $wpdb;
        $wpdb->delete($this->tableItems, ['product_id' => $id]);
        return (bool) $wpdb->delete($this->tableProducts, ['id' => $id]);
    }
}