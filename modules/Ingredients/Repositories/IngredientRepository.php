<?php

namespace KitchenManager\Modules\Ingredients\Repositories;

class IngredientRepository 
{
    private string $table;

    public function __construct() 
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'km_ingredients';
        $this->createTableIfNotExists();
    }

    public function createTableIfNotExists(): void 
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'food',
            package_type VARCHAR(50) NOT NULL DEFAULT 'Embalagem',
            package_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            package_size DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
            package_unit VARCHAR(20) NOT NULL DEFAULT 'g',
            unit VARCHAR(20) NOT NULL DEFAULT 'g',
            density DECIMAL(10, 4) NULL DEFAULT 1.0000,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $cols = $wpdb->get_col("DESC {$this->table}", 0);
        if (!in_array('category', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'food' AFTER name");
        }
        if (!in_array('package_type', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN package_type VARCHAR(50) NOT NULL DEFAULT 'Embalagem' AFTER category");
        }
        if (!in_array('package_cost', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN package_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER package_type");
        }
        if (!in_array('package_size', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN package_size DECIMAL(10, 4) NOT NULL DEFAULT 1.0000 AFTER package_cost");
        }
        if (!in_array('package_unit', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN package_unit VARCHAR(20) NOT NULL DEFAULT 'g' AFTER package_size");
        }
        if (!in_array('unit', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN unit VARCHAR(20) NOT NULL DEFAULT 'g' AFTER package_unit");
        }
        if (!in_array('density', $cols)) {
            $wpdb->query("ALTER TABLE {$this->table} ADD COLUMN density DECIMAL(10, 4) NULL DEFAULT 1.0000 AFTER unit");
        }
    }

    public function getAll(string $orderby = 'name', string $order = 'ASC'): array 
{
    global $wpdb; // 👈 Importa a conexão global do WordPress

    // Whitelist segura de colunas para ordenação
    $allowed_columns = [
        'name'         => 'name',
        'category'     => 'category',
        'package_cost' => 'package_cost',
        'package_size' => 'package_size',
        'unit'         => 'unit',
        'created_at'   => 'created_at'
    ];

    $orderby = array_key_exists($orderby, $allowed_columns) ? $allowed_columns[$orderby] : 'name';
    $order   = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

    // Executa a query utilizando a variável global $wpdb
    $results = $wpdb->get_results(
        "SELECT * FROM {$this->table} ORDER BY {$orderby} {$order}"
    );

    return $results ?: [];
}

    public function getById(int $id): ?object 
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id));
        if ($row) {
            $row->package_unit = $row->package_unit ?? $row->unit ?? 'g';
            if ($row->category === 'tag') $row->category = 'finishing';
            if ($row->category === 'flower') $row->category = 'decoration';
        }
        return $row ?: null;
    }

    public function saveBatch(array $items): bool 
    {
        global $wpdb;

        foreach ($items as $item) {
            $id   = isset($item['id']) && (int)$item['id'] > 0 ? (int)$item['id'] : 0;
            $name = isset($item['name']) ? sanitize_text_field(wp_unslash($item['name'])) : '';

            if (empty($name)) continue;

            $rawCost = str_replace(',', '.', (string)($item['package_cost'] ?? '0'));
            $rawSize = str_replace(',', '.', (string)($item['package_size'] ?? '1'));
            $cost = max(0, (float)$rawCost);
            $size = max(0.0001, (float)$rawSize);

            $pkgUnit   = !empty($item['package_unit']) ? sanitize_text_field(wp_unslash($item['package_unit'])) : 'g';
            $usageUnit = !empty($item['unit']) ? sanitize_text_field(wp_unslash($item['unit'])) : $pkgUnit;

            $data = [
                'name'         => $name,
                'category'     => !empty($item['category']) ? sanitize_text_field(wp_unslash($item['category'])) : 'food',
                'package_type' => !empty($item['package_type']) ? sanitize_text_field(wp_unslash($item['package_type'])) : 'Embalagem',
                'package_cost' => $cost,
                'package_size' => $size,
                'package_unit' => $pkgUnit,
                'unit'         => $usageUnit,
                'density'      => !empty($item['density']) ? (float)str_replace(',', '.', (string)$item['density']) : 1.0,
            ];

            if ($id > 0) {
                $wpdb->update($this->table, $data, ['id' => $id]);
            } else {
                $wpdb->insert($this->table, $data);
            }
        }

        return true;
    }

    public function delete(int $id): bool 
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->table, ['id' => $id]);
    }
}