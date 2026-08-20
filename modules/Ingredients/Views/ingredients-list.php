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

<div class="wrap km-admin-wrap">
    
    <div class="km-header-bar">
        <div>
            <h1 class="km-page-title">📦 Cadastro de Insumos & Custos</h1>
            <p class="km-page-desc">Cadastre os insumos adquiridos no fornecedor, os formatos de compra e as unidades reais de consumo na cozinha.</p>
        </div>
        <div>
            <button type="button" class="button button-secondary" onclick="document.getElementById('km-import-modal').style.display='flex'">
                📥 Importar CSV / Texto
            </button>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
        <div class="notice notice-success is-dismissible km-notice"><p><strong>✅ Insumos salvos e custos atualizados com sucesso!</strong></p></div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="notice notice-info is-dismissible km-notice"><p><strong>🗑️ Insumo excluído com sucesso.</strong></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-ingredients-form">
        <input type="hidden" name="action" value="km_save_ingredients">
        <?php wp_nonce_field('km_save_ingredients_action', 'km_ingredients_nonce'); ?>

        <div class="km-card" style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped km-table" id="km-ingredients-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><a href="<?php echo esc_url($sort_url('name')); ?>">Nome do Insumo</a></th>
                        <th style="width: 12%;"><a href="<?php echo esc_url($sort_url('category')); ?>">Categoria</a></th>
                        <th style="width: 12%;"><a href="<?php echo esc_url($sort_url('package_type')); ?>">Embalagem</a></th>
                        <th style="width: 9%;"><a href="<?php echo esc_url($sort_url('package_size')); ?>">Conteúdo</a></th>
                        <th style="width: 9%;">Unid. Embalagem</th>
                        <th style="width: 10%;"><a href="<?php echo esc_url($sort_url('package_cost')); ?>">Custo (R$)</a></th>
                        <th style="width: 9%;">
                            Unid. Uso 
                            <span class="km-tooltip-trigger" data-tooltip="Como você usa este insumo na receita? (Ex: Compra em Kg, mas usa em gramas).">ℹ️</span>
                        </th>
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
                            if ($pkgUnit === 'kg' && $unit === 'g') $effectiveSize = $pkgSize * 1000;
                            elseif ($pkgUnit === 'l' && $unit === 'ml') $effectiveSize = $pkgSize * 1000;
                            elseif ($pkgUnit === 'g' && $unit === 'kg') $effectiveSize = $pkgSize / 1000;
                            elseif ($pkgUnit === 'ml' && $unit === 'l') $effectiveSize = $pkgSize / 1000;
                            
                            $unitCost = ($effectiveSize > 0) ? ($pkgCost / $effectiveSize) : 0;
                        ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="ingredients[<?php echo $idx; ?>][id]" value="<?php echo esc_attr($ing->id); ?>">
                                    <input type="text" name="ingredients[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($ing->name); ?>" required class="widefat km-input" placeholder="Ex: Açúcar Refinado">
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][category]" class="widefat km-select km-cat-sel">
                                        <?php foreach ($categories as $cKey => $cLabel): ?>
                                            <option value="<?php echo esc_attr($cKey); ?>" <?php selected($cat, $cKey); ?>><?php echo esc_html($cLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][package_type]" class="widefat km-select km-pkg-type-sel">
                                        <?php foreach ($packageTypes as $pType): ?>
                                            <option value="<?php echo esc_attr($pType); ?>" <?php selected($pkgType, $pType); ?>><?php echo esc_html($pType); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="any" min="0.0001" name="ingredients[<?php echo $idx; ?>][package_size]" value="<?php echo esc_attr($pkgSize); ?>" class="widefat km-input km-inp-size" required>
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][package_unit]" class="widefat km-select km-sel-pkg-unit">
                                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                                            <option value="<?php echo esc_attr($uKey); ?>" <?php selected($pkgUnit, $uKey); ?>><?php echo esc_html($uLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="ingredients[<?php echo $idx; ?>][package_cost]" value="<?php echo esc_attr($pkgCost); ?>" class="widefat km-input km-inp-cost" required>
                                </td>
                                <td>
                                    <select name="ingredients[<?php echo $idx; ?>][unit]" class="widefat km-select km-sel-unit">
                                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                                            <option value="<?php echo esc_attr($uKey); ?>" <?php selected($unit, $uKey); ?>><?php echo esc_html($uLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <strong class="km-unit-cost-preview" style="color: #2271b1;">R$ <?php echo number_format($unitCost, 4, ',', '.'); ?> / <?php echo esc_html($unit); ?></strong>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_delete_ingredient&id=' . $ing->id), 'km_delete_ingredient_' . $ing->id)); ?>" 
                                       class="km-btn-del" title="Excluir insumo" onclick="return confirm('Tem certeza que deseja remover este insumo permanentemente?');">✕</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="km-card-footer">
                <button type="button" class="button button-secondary" id="km-add-row-btn">➕ Adicionar Insumo</button>
                <button type="submit" class="button button-primary button-large km-btn-save">💾 Salvar Alterações</button>
            </div>
        </div>
    </form>
</div>

<div id="km-import-modal" class="km-modal-overlay" style="display: none;">
    <div class="km-modal-box">
        <h2 class="km-modal-title">📥 Importação Rápida de Insumos</h2>
        <p class="km-modal-desc">
            Formato esperado das colunas:<br>
            <code style="background: #f0f0f1; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 6px;">Nome | Categoria | Preço Pacote | Qtd Pacote | Unidade</code>
        </p>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="km_import_ingredients">
            <?php wp_nonce_field('km_import_ingredients_nonce'); ?>

            <div class="km-modal-field">
                <label><strong>Opção A: Fazer upload de arquivo CSV</strong></label>
                <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" style="margin-top: 5px;">
            </div>

            <div class="km-modal-field">
                <label><strong>Opção B: Ou cole o texto da sua planilha abaixo</strong></label>
                <textarea name="raw_textarea" rows="5" class="km-input" style="width: 100%; margin-top: 5px; font-family: monospace;" placeholder="Farinha de Trigo; food; 28.50; 5; kg&#10;Leite Condensado; food; 7.20; 395; g"></textarea>
            </div>

            <div class="km-modal-actions">
                <button type="button" class="button button-secondary" onclick="document.getElementById('km-import-modal').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Processar Importação</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Design System Core - Kitchen Manager */
.km-admin-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.km-header-bar { background: #ffffff; padding: 18px 24px; border: 1px solid #ccd0d4; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-page-title { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; font-weight: 600; }
.km-page-desc { margin: 0; color: #646970; font-size: 14px; }
.km-notice { margin-top: 15px; margin-bottom: 20px; border-radius: 4px; border-left-width: 4px; }

/* Cards & Layout */
.km-card { background: #ffffff; border: 1px solid #ccd0d4; padding: 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden; }
.km-card-footer { padding: 16px 20px; background: #fcfcfc; border-top: 1px solid #ccd0d4; display: flex; justify-content: space-between; align-items: center; }

/* Formulários e Inputs */
.km-input, .km-select { padding: 6px 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; box-sizing: border-box; }
.km-input:focus, .km-select:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.km-btn-save { height: 40px; font-size: 14px; font-weight: 600; padding: 0 24px; }
.km-btn-del { color: #b32d2e; text-decoration: none; font-weight: bold; font-size: 14px; padding: 4px 8px; border-radius: 4px; display: inline-block; transition: background 0.2s; }
.km-btn-del:hover { background: #f8d7da; }

/* Tabelas */
.km-table { border: none !important; margin: 0 !important; }
.km-table thead th { background: #f6f7f7; padding: 12px 10px; font-weight: 600; color: #2c3338; border-bottom: 1px solid #ccd0d4; font-size: 13px; }
.km-table tbody td { padding: 10px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }

/* Tooltips (O mesmo de pricing) */
.km-tooltip-trigger { display: inline-block; cursor: help; color: #2271b1; font-weight: bold; position: relative; margin-left: 4px; }
.km-tooltip-trigger:hover::after {
    content: attr(data-tooltip); position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%);
    background: #1d2327; color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 11.5px;
    line-height: 1.4; width: 220px; z-index: 999; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    font-weight: normal; text-align: left;
}
.km-tooltip-trigger:hover::before {
    content: ""; position: absolute; bottom: 105%; left: 50%; transform: translateX(-50%);
    border-width: 5px; border-style: solid; border-color: #1d2327 transparent transparent transparent; z-index: 999;
}

/* Modal */
.km-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center; }
.km-modal-box { background: #fff; padding: 24px 30px; border-radius: 8px; width: 600px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.km-modal-title { margin: 0 0 10px 0; font-size: 20px; color: #1d2327; }
.km-modal-desc { color: #50575e; font-size: 13px; margin-bottom: 20px; }
.km-modal-field { margin-bottom: 18px; }
.km-modal-field label { color: #1d2327; font-size: 13px; }
.km-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; border-top: 1px solid #f0f0f1; padding-top: 16px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('km-ingredients-rows');
    const addBtn = document.getElementById('km-add-row-btn');

    // Previne que a tecla "Enter" envie o formulário acidentalmente
    document.getElementById('km-ingredients-form').addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
            event.preventDefault();
        }
    });

    function updateRowCalc(tr) {
        const costEl    = tr.querySelector('.km-inp-cost');
        const sizeEl    = tr.querySelector('.km-inp-size');
        const pkgUnitEl = tr.querySelector('.km-sel-pkg-unit');
        const unitEl    = tr.querySelector('.km-sel-unit');
        const preview   = tr.querySelector('.km-unit-cost-preview');
        
        if (!costEl || !sizeEl || !pkgUnitEl || !unitEl || !preview) return;

        const cost    = parseFloat(costEl.value) || 0;
        const size    = parseFloat(sizeEl.value) || 1;
        const pkgUnit = pkgUnitEl.value || 'g';
        const unit    = unitEl.value || 'g';
        
        let effectiveSize = size;
        if (pkgUnit === 'kg' && unit === 'g') effectiveSize = size * 1000;
        else if (pkgUnit === 'l' && unit === 'ml') effectiveSize = size * 1000;
        else if (pkgUnit === 'g' && unit === 'kg') effectiveSize = size / 1000;
        else if (pkgUnit === 'ml' && unit === 'l') effectiveSize = size / 1000;

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
                    <input type="text" name="ingredients[${idx}][name]" required class="widefat km-input" placeholder="Ex: Farinha de Trigo">
                </td>
                <td>
                    <select name="ingredients[${idx}][category]" class="widefat km-select km-cat-sel">
                        <?php foreach ($categories as $cKey => $cLabel): ?>
                            <option value="<?php echo esc_attr($cKey); ?>"><?php echo esc_html($cLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="ingredients[${idx}][package_type]" class="widefat km-select km-pkg-type-sel">
                        <?php foreach ($packageTypes as $pType): ?>
                            <option value="<?php echo esc_attr($pType); ?>"><?php echo esc_html($pType); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" step="any" min="0.0001" name="ingredients[${idx}][package_size]" value="1000" class="widefat km-input km-inp-size" required>
                </td>
                <td>
                    <select name="ingredients[${idx}][package_unit]" class="widefat km-select km-sel-pkg-unit">
                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                            <option value="<?php echo esc_attr($uKey); ?>"><?php echo esc_html($uLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="ingredients[${idx}][package_cost]" value="0.00" class="widefat km-input km-inp-cost" required>
                </td>
                <td>
                    <select name="ingredients[${idx}][unit]" class="widefat km-select km-sel-unit">
                        <?php foreach ($unitsList as $uKey => $uLabel): ?>
                            <option value="<?php echo esc_attr($uKey); ?>"><?php echo esc_html($uLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="vertical-align: middle;">
                    <strong class="km-unit-cost-preview" style="color: #2271b1;">R$ 0,0000 / g</strong>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <button type="button" class="km-btn-del" title="Remover Insumo" onclick="this.closest('tr').remove();">✕</button>
                </td>
            `;
            tbody.appendChild(tr);
            bindRow(tr);
        });
    }
});
</script>