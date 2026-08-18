<?php
if (!defined('ABSPATH')) exit;

$categories = [
    'food'       => 'Ingrediente',
    'packaging'  => 'Embalagem',
    'finishing'  => 'Acabamento',
    'decoration' => 'Decoração',
    'other'      => 'Outro'
];

$packageTypes = ['Pacote', 'Lata', 'Caixa', 'Garrafa', 'Pote', 'Barra', 'Saco', 'Rolo / Fita', 'Cento', 'Unidade'];
$unitsList    = ['g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'l' => 'L', 'un' => 'un'];

$current_orderby = sanitize_key($_GET['orderby'] ?? 'name');
$current_order   = strtoupper(sanitize_key($_GET['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

$sort_url = function($column) use ($current_orderby, $current_order) {
    $next_order = ($current_orderby === $column && $current_order === 'ASC') ? 'desc' : 'asc';
    return add_query_arg(['orderby' => $column, 'order' => $next_order]);
};
?>

<div class="wrap km-ingredients-wrap">
    <div class="km-header-bar">
        <h1 style="margin: 0; font-size: 22px; color: #1d2327;">📦 Cadastro de Insumos & Custos</h1>
        <p style="margin: 4px 0 0 0; color: #646970;">Cadastre os insumos adquiridos no fornecedor, os formatos de compra e as unidades reais de consumo na cozinha.</p>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
        <div class="notice notice-success is-dismissible" style="margin-top: 15px;"><p><strong>✅ Insumos salvos e custos atualizados com sucesso!</strong></p></div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="notice notice-info is-dismissible" style="margin-top: 15px;"><p><strong>🗑️ Insumo excluído com sucesso.</strong></p></div>
    <?php endif; ?>

    <div style="margin-top: 15px; margin-bottom: 10px;">
        <button type="button" class="button" onclick="document.getElementById('km-import-modal').style.display='flex'">📥 Importar CSV / Texto</button>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-ingredients-form">
        <input type="hidden" name="action" value="km_save_ingredients">
        <?php wp_nonce_field('km_save_ingredients_action', 'km_ingredients_nonce'); ?>

        <div class="km-card" style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped" id="km-ingredients-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><a href="<?php echo esc_url($sort_url('name')); ?>">Nome do Insumo</a></th>
                        <th style="width: 12%;"><a href="<?php echo esc_url($sort_url('category')); ?>">Categoria</a></th>
                        <th style="width: 12%;"><a href="<?php echo esc_url($sort_url('package_type')); ?>">Tipo de Embalagem</a></th>
                        <th style="width: 9%;"><a href="<?php echo esc_url($sort_url('package_size')); ?>">Conteúdo</a></th>
                        <th style="width: 9%;">Unid. Embalagem</th>
                        <th style="width: 10%;"><a href="<?php echo esc_url($sort_url('package_cost')); ?>">Custo (R$)</a></th>
                        <th style="width: 9%;">Unid. de Uso</th>
                        <th style="width: 11%;">Custo / Uso</th>
                        <th style="width: 3%; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody id="km-ingredients-rows">
                    <?php if (!empty($ingredients)): ?>
                        <?php foreach ($ingredients as $idx => $ing): 
                            $pkgCost  = (float)($ing->package_cost ?? 0);
                            $pkgSize  = (float)($ing->package_size ?? 1);
                            $pkgUnit  = $ing->package_unit ?? $ing->unit ?? 'g';
                            $unit     = $ing->unit ?? 'g';
                            $pkgType  = $ing->package_type ?? 'Pacote';
                            $cat      = $ing->category ?? 'food';

                            $effectiveSize = $pkgSize;
                            if ($pkgUnit === 'kg' && $unit === 'g') {
                                $effectiveSize = $pkgSize * 1000;
                            } elseif ($pkgUnit === 'l' && $unit === 'ml') {
                                $effectiveSize = $pkgSize * 1000;
                            } elseif ($pkgUnit === 'g' && $unit === 'kg') {
                                $effectiveSize = $pkgSize / 1000;
                            } elseif ($pkgUnit === 'ml' && $unit === 'l') {
                                $effectiveSize = $pkgSize / 1000;
                            }
                            $unitCost = ($effectiveSize > 0) ? ($pkgCost / $effectiveSize) : 0;
                        ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="ingredients[<?php echo $idx; ?>][id]" value="<?php echo esc_attr($ing->id); ?>">
                                    <input type="text" name="ingredients[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($ing->name); ?>" required class="widefat" placeholder="Ex: Açúcar Refinado">
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][category]" class="widefat km-cat-sel">
                                        <?php foreach ($categories as $cKey => $cLabel): ?>
                                            <option value="<?php echo esc_attr($cKey); ?>" <?php selected($cat, $cKey); ?>><?php echo esc_html($cLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][package_type]" class="widefat km-pkg-type-sel">
                                        <?php foreach ($packageTypes as $pType): ?>
                                            <option value="<?php echo esc_attr($pType); ?>" <?php selected($pkgType, $pType); ?>><?php echo esc_html($pType); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="any" min="0.0001" name="ingredients[<?php echo $idx; ?>][package_size]" value="<?php echo esc_attr($pkgSize); ?>" class="widefat km-inp-size" required>
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][package_unit]" class="widefat km-sel-pkg-unit">
                                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                                            <option value="<?php echo esc_attr($uKey); ?>" <?php selected($pkgUnit, $uKey); ?>><?php echo esc_html($uLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="ingredients[<?php echo $idx; ?>][package_cost]" value="<?php echo esc_attr($pkgCost); ?>" class="widefat km-inp-cost" required>
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][unit]" class="widefat km-sel-unit">
                                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                                            <option value="<?php echo esc_attr($uKey); ?>" <?php selected($unit, $uKey); ?>><?php echo esc_html($uLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <strong class="km-unit-cost-preview">R$ <?php echo number_format($unitCost, 4, ',', '.'); ?> / <?php echo esc_html($unit); ?></strong>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_delete_ingredient&id=' . $ing->id), 'km_delete_ingredient_' . $ing->id)); ?>" 
                                       class="km-btn-del" title="Excluir insumo" onclick="return confirm('Remover este insumo permanentemente?');">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                <button type="button" class="button button-secondary" id="km-add-row-btn">➕ Adicionar Novo Insumo</button>
                <button type="submit" class="button button-primary button-large" style="height: 40px; font-size: 14px; font-weight: 600;">💾 Salvar Todos os Insumos</button>
            </div>
        </div>
    </form>
</div>

<div id="km-import-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 25px; border-radius: 8px; width: 600px; max-width: 90%;">
        <h2>📥 Importação Rápida de Insumos</h2>
        <p style="color: #666; font-size: 13px;">
            Formato esperado das colunas:<br>
            <code>Nome | Categoria | Preço Pacote | Qtd Pacote | Unidade</code>
        </p>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="km_import_ingredients">
            <?php wp_nonce_field('km_import_ingredients_nonce'); ?>

            <div style="margin-bottom: 15px;">
                <label><strong>Opção A: Fazer upload de arquivo CSV</strong></label><br>
                <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" style="margin-top: 5px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label><strong>Opção B: Ou cole o texto da sua planilha abaixo</strong></label>
                <textarea name="raw_textarea" rows="6" style="width: 100%; margin-top: 5px; font-family: monospace;" placeholder="Farinha de Trigo; food; 28.50; 5; kg&#10;Leite Condensado; food; 7.20; 395; g"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="button" onclick="document.getElementById('km-import-modal').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Processar Importação</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('km-ingredients-rows');
    const addBtn = document.getElementById('km-add-row-btn');

    function updateRowCalc(tr) {
        const costEl  = tr.querySelector('.km-inp-cost');
        const sizeEl  = tr.querySelector('.km-inp-size');
        const pkgUnitEl = tr.querySelector('.km-sel-pkg-unit');
        const unitEl  = tr.querySelector('.km-sel-unit');
        const preview = tr.querySelector('.km-unit-cost-preview');
        
        if (!costEl || !sizeEl || !pkgUnitEl || !unitEl || !preview) return;

        const cost    = parseFloat(costEl.value) || 0;
        const size    = parseFloat(sizeEl.value) || 1;
        const pkgUnit = pkgUnitEl.value || 'g';
        const unit    = unitEl.value || 'g';
        
        let effectiveSize = size;
        if (pkgUnit === 'kg' && unit === 'g') {
            effectiveSize = size * 1000;
        } else if (pkgUnit === 'l' && unit === 'ml') {
            effectiveSize = size * 1000;
        } else if (pkgUnit === 'g' && unit === 'kg') {
            effectiveSize = size / 1000;
        } else if (pkgUnit === 'ml' && unit === 'l') {
            effectiveSize = size / 1000;
        }

        const unitCost = effectiveSize > 0 ? (cost / effectiveSize) : 0;
        preview.textContent = 'R$ ' + unitCost.toFixed(4).replace('.', ',') + ' / ' + unit;
    }

    function bindRow(tr) {
        tr.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', () => updateRowCalc(tr));
            el.addEventListener('change', () => updateRowCalc(tr));
        });
    }

    tbody.querySelectorAll('tr').forEach(bindRow);

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const idx = 'new_' + Date.now();
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" name="ingredients[${idx}][name]" required class="widefat" placeholder="Ex: Farinha de Trigo">
                </td>
                <td>
                    <select name="ingredients[${idx}][category]" class="widefat km-cat-sel">
                        <?php foreach ($categories as $cKey => $cLabel): ?>
                            <option value="<?php echo esc_attr($cKey); ?>"><?php echo esc_html($cLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="ingredients[${idx}][package_type]" class="widefat km-pkg-type-sel">
                        <?php foreach ($packageTypes as $pType): ?>
                            <option value="<?php echo esc_attr($pType); ?>"><?php echo esc_html($pType); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" step="any" min="0.0001" name="ingredients[${idx}][package_size]" value="1000" class="widefat km-inp-size" required>
                </td>
                <td>
                    <select name="ingredients[${idx}][package_unit]" class="widefat km-sel-pkg-unit">
                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                            <option value="<?php echo esc_attr($uKey); ?>"><?php echo esc_html($uLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="ingredients[${idx}][package_cost]" value="0.00" class="widefat km-inp-cost" required>
                </td>
                <td>
                    <select name="ingredients[${idx}][unit]" class="widefat km-sel-unit">
                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                            <option value="<?php echo esc_attr($uKey); ?>"><?php echo esc_html($uLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="vertical-align: middle;">
                    <strong class="km-unit-cost-preview">R$ 0,0000 / g</strong>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <button type="button" class="button button-small" style="color: #b32d2e;" onclick="this.closest('tr').remove();">🗑️</button>
                </td>
            `;
            tbody.appendChild(tr);
            bindRow(tr);
        });
    }
});
</script>