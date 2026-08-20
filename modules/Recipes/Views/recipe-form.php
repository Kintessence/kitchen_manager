<?php
if (!defined('ABSPATH')) exit;

$editingId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingRecipe = null;
if ($editingId > 0) {
    foreach ($recipes as $r) {
        $rId = is_object($r) ? ($r->id ?? 0) : ($r['id'] ?? 0);
        if ($rId == $editingId) {
            $editingRecipe = $r;
            break;
        }
    }
}
$isEditing = !empty($editingRecipe);

function km_get_prop($obj, array $possibleKeys, $default = '') {
    if (is_array($obj)) {
        foreach ($possibleKeys as $k) {
            if (isset($obj[$k])) return $obj[$k];
        }
    } elseif (is_object($obj)) {
        foreach ($possibleKeys as $k) {
            if (isset($obj->$k)) return $obj->$k;
        }
    }
    return $default;
}

$editName  = km_get_prop($editingRecipe, ['name'], '');
$editYield = (float) km_get_prop($editingRecipe, ['yield', 'portions', 'yield_amount', 'yield_quantity'], 1.0);
$editItems = km_get_prop($editingRecipe, ['items', 'ingredients'], []);
?>

<div class="wrap km-admin-wrap">
    
    <div class="km-header-bar">
        <div>
            <h1 class="km-page-title">📋 Fichas Técnicas & Formação de Receitas</h1>
            <p class="km-page-desc">Lance insumos na medida exata de preparo (g, kg, ml, L ou embalagem inteira) com conversão e custo automáticos.</p>
        </div>
        <div>
            </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
        <div class="notice notice-success is-dismissible km-notice"><p><strong>✅ Ficha técnica salva com sucesso!</strong></p></div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="notice notice-info is-dismissible km-notice"><p><strong>🗑️ Ficha técnica removida com sucesso.</strong></p></div>
    <?php endif; ?>

    <div class="km-split-grid">
        
        <div class="km-card km-form-col">
            <div class="km-card-header">
                <h2 class="km-card-title"><?php echo $isEditing ? '✏️ Editar Ficha Técnica' : '➕ Nova Ficha Técnica'; ?></h2>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-recipe-form" style="padding: 20px;">
                <input type="hidden" name="action" value="km_save_recipe">
                <input type="hidden" name="id" value="<?php echo $isEditing ? (int) km_get_prop($editingRecipe, ['id'], 0) : 0; ?>">
                <?php wp_nonce_field('km_save_recipe_action', 'km_recipe_nonce'); ?>

                <div class="km-field-group">
                    <label for="km-r-name">Nome da Receita / Produto:</label>
                    <input type="text" id="km-r-name" name="name" value="<?php echo esc_attr($editName); ?>" placeholder="Ex: Bolo de Chocolate, Pudim..." required class="km-input widefat">
                </div>

                <div class="km-field-group">
                    <label for="km-r-yield">
                        Rendimento da Fornada (Porções/Unidades):
                        <span class="km-tooltip-trigger" data-tooltip="Quantas unidades finais ou fatias esta receita inteira produz? Isso define o custo unitário.">ℹ️</span>
                    </label>
                    <input type="number" id="km-r-yield" name="yield" value="<?php echo esc_attr($editYield); ?>" step="any" min="0.0001" required class="km-input widefat">
                </div>

                <div class="km-section-subhead">
                    <span style="font-weight: 600; font-size: 13px; text-transform: uppercase; color: #2c3338;">Insumos da Receita</span>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="km-btn-link-del" id="km-clear-all-ings-btn">🗑️ Limpar Tudo</button>
                        <button type="button" class="button button-secondary button-small" id="km-add-ing-btn">➕ Adicionar Insumo</button>
                    </div>
                </div>

                <div id="km-recipe-items-box" class="km-items-container">
                    <?php 
                    if (!empty($editItems) && is_iterable($editItems)):
                        $rowIdx = 0;
                        foreach ($editItems as $it): 
                            $itIngId   = (int) km_get_prop($it, ['ingredient_id', 'ingredientId', 'id'], 0);
                            $itQty     = (float) km_get_prop($it, ['quantity', 'quantity_used', 'quantityUsed'], 1.0);
                            $itMeasure = (string) km_get_prop($it, ['measure_type', 'measureType', 'unit_type'], 'unit');
                            $itCost    = (float) km_get_prop($it, ['cost', 'total_cost', 'totalCost'], 0.0);
                    ?>
                        <div class="km-recipe-item-card">
                            <button type="button" class="km-item-del-btn" title="Remover Insumo">✕</button>
                            
                            <div class="km-item-field">
                                <label style="font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600;">Insumo:</label>
                                <select name="items[<?php echo $rowIdx; ?>][ingredient_id]" class="km-select widefat km-select-ing" required>
                                    <option value="">-- Escolha um insumo --</option>
                                    <?php foreach ($ingredients as $ing): 
                                        $pkgSizeItem = (float) km_get_prop($ing, ['package_size', 'packageSize', 'package_quantity'], 1.0);
                                        $pkgCostItem = (float) km_get_prop($ing, ['package_cost', 'packageCost', 'cost'], 0.0);
                                        $pkgUnitItem = strtolower((string)km_get_prop($ing, ['package_unit'], 'g'));
                                        $unitItem    = strtolower((string)km_get_prop($ing, ['unit', 'usage_unit', 'usageUnit'], 'g'));
                                        $pkgTypeItem = km_get_prop($ing, ['package_type'], 'Embalagem');
                                        $ingIdItem   = (int) km_get_prop($ing, ['id'], 0);

                                        $effectiveSize = $pkgSizeItem;
                                        if ($pkgUnitItem === 'kg' && $unitItem === 'g') $effectiveSize = $pkgSizeItem * 1000;
                                        elseif ($pkgUnitItem === 'l' && $unitItem === 'ml') $effectiveSize = $pkgSizeItem * 1000;
                                        elseif ($pkgUnitItem === 'g' && $unitItem === 'kg') $effectiveSize = $pkgSizeItem / 1000;
                                        elseif ($pkgUnitItem === 'ml' && $unitItem === 'l') $effectiveSize = $pkgSizeItem / 1000;

                                        $unitCostItem = ($effectiveSize > 0) ? ($pkgCostItem / $effectiveSize) : 0.0;
                                    ?>
                                        <option value="<?php echo esc_attr($ingIdItem); ?>" 
                                                data-unit-cost="<?php echo esc_attr($unitCostItem); ?>"
                                                data-pkg-cost="<?php echo esc_attr($pkgCostItem); ?>"
                                                data-pkg-size="<?php echo esc_attr($pkgSizeItem); ?>"
                                                data-unit="<?php echo esc_attr($unitItem); ?>"
                                                data-pkg-type="<?php echo esc_attr($pkgTypeItem); ?>"
                                                <?php echo ($itIngId === $ingIdItem) ? 'selected' : ''; ?>>
                                            <?php echo esc_html(km_get_prop($ing, ['name'], 'Insumo')); ?> (R$ <?php echo number_format($unitCostItem, 4, ',', '.'); ?>/<?php echo esc_html($unitItem); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="km-item-row-flex">
                                <div class="km-qty-box">
                                    <label style="font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600;">Qtd e Medida:</label>
                                    <div class="km-inline-inputs">
                                        <input type="number" name="items[<?php echo $rowIdx; ?>][quantity]" value="<?php echo esc_attr($itQty); ?>" step="any" min="0.0001" class="km-input km-input-qty" required>
                                        <select name="items[<?php echo $rowIdx; ?>][measure_type]" class="km-select km-select-measure" data-current-measure="<?php echo esc_attr($itMeasure); ?>">
                                            <option value="<?php echo esc_attr($itMeasure); ?>" selected><?php echo esc_html($itMeasure); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="km-subtotal-box">
                                    <span style="font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600;">Subtotal:</span>
                                    <strong class="km-subtotal-val">R$ <?php echo number_format($itCost, 2, ',', '.'); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $rowIdx++;
                        endforeach; 
                    endif;
                    ?>
                </div>

                <div class="km-totals-banner">
                    <div style="font-size: 13px; color: #50575e;">
                        Custo Total Fornada: <strong id="km-lbl-batch-cost" style="color: #1d2327;">R$ 0,00</strong>
                    </div>
                    <div style="font-size: 16px; color: #2271b1; font-weight: 700; margin-top: 4px;">
                        Custo Unitário / Porção: <span id="km-lbl-unit-cost">R$ 0,00</span>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="button button-primary button-large km-btn-save" style="flex: 1;">
                        💾 <?php echo $isEditing ? 'Atualizar Ficha Técnica' : 'Salvar Ficha Técnica'; ?>
                    </button>
                    <?php if ($isEditing): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-recipes')); ?>" class="button button-secondary button-large" style="line-height: 38px;">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="km-cards-col">
            <h2 style="font-size: 16px; margin: 0 0 16px 0; color: #1d2327;">📚 Fichas Técnicas Salvas (<?php echo count($recipes); ?>)</h2>

            <div class="km-cards-stack">
                <?php if (empty($recipes)): ?>
                    <div class="km-empty-box">Nenhuma ficha cadastrada ainda. Use o formulário ao lado para criar a primeira.</div>
                <?php else: ?>
                    <?php foreach ($recipes as $r): 
                        $rId       = (int) km_get_prop($r, ['id'], 0);
                        $rName     = km_get_prop($r, ['name'], 'Sem Nome');
                        $rYield    = (float) km_get_prop($r, ['yield', 'portions', 'yield_amount', 'yield_quantity'], 1.0);
                        $rUnitCost = (float) km_get_prop($r, ['unitCost', 'unit_cost', 'cost_per_unit', 'unit_portion_cost'], 0.0);
                        $rTotalCost= (float) km_get_prop($r, ['totalCost', 'total_cost', 'total_batch_cost', 'cost'], ($rUnitCost * $rYield));
                        $rItems    = km_get_prop($r, ['items', 'ingredients'], []);
                    ?>
                        <div class="km-recipe-card">
                            <div class="km-rc-header">
                                <div>
                                    <h3 class="km-rc-title"><?php echo esc_html($rName); ?></h3>
                                    <span class="km-rc-sub">Rendimento: <?php echo $rYield; ?> un | Fornada: R$ <?php echo number_format($rTotalCost, 2, ',', '.'); ?></span>
                                </div>
                                <div class="km-rc-cost-block">
                                    <span class="km-rc-cost-label">Custo / Unidade:</span>
                                    <strong class="km-rc-cost-val">R$ <?php echo number_format($rUnitCost, 2, ',', '.'); ?></strong>
                                </div>
                            </div>

                            <div class="km-rc-composition">
                                <span class="km-rc-comp-title">Composição:</span>
                                <div class="km-tags-flex">
                                    <?php if (!empty($rItems) && is_iterable($rItems)): ?>
                                        <?php foreach ($rItems as $it): 
                                            $itName    = km_get_prop($it, ['name', 'ingredient_name', 'ingredientName'], 'Insumo');
                                            $itQty     = (float) km_get_prop($it, ['quantity', 'quantity_used', 'quantityUsed'], 0.0);
                                            $itCost    = (float) km_get_prop($it, ['cost', 'total_cost', 'totalCost'], 0.0);
                                            $itMeasure = (string) km_get_prop($it, ['measure_type', 'measureType', 'unit_type'], 'unit');
                                            $itUnit    = (string) km_get_prop($it, ['unit', 'usage_unit', 'usageUnit'], 'g');
                                            $itPkgType = (string) km_get_prop($it, ['package_type', 'pkg_type'], 'Embalagem');
                                            $itId      = (int) km_get_prop($it, ['id'], 0);
                                            
                                            if ($itMeasure === 'pkg') {
                                                $labelDisplay = "{$itQty}x {$itPkgType}";
                                            } elseif ($itMeasure === 'g_from_kg' || $itMeasure === 'g') {
                                                $labelDisplay = "{$itQty} g";
                                            } elseif ($itMeasure === 'kg_from_g' || $itMeasure === 'kg') {
                                                $labelDisplay = "{$itQty} kg";
                                            } elseif ($itMeasure === 'ml_from_l' || $itMeasure === 'ml') {
                                                $labelDisplay = "{$itQty} ml";
                                            } elseif ($itMeasure === 'l_from_ml' || $itMeasure === 'l' || $itMeasure === 'L') {
                                                $labelDisplay = "{$itQty} L";
                                            } elseif ($itMeasure === 'unit' || empty($itMeasure)) {
                                                $labelDisplay = "{$itQty} {$itUnit}";
                                            } else {
                                                $labelDisplay = "{$itQty} {$itMeasure}";
                                            }
                                        ?>
                                            <span class="km-tag-pill">
                                                <?php echo esc_html($itName); ?>: <?php echo esc_html($labelDisplay); ?>
                                                <?php if ($itId > 0): ?>
                                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_delete_recipe_item&recipe_id=' . $rId . '&item_id=' . $itId), 'km_delete_item_' . $itId)); ?>" 
                                                       class="km-tag-del" title="Remover insumo da ficha" onclick="return confirm('Remover este insumo da receita?');">✕</a>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #8c8f94;">Sem insumos vinculados.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="km-rc-footer">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-pricing&cost=' . $rUnitCost . '&recipe_name=' . urlencode($rName))); ?>" class="button button-small km-btn-blue">
                                    Simular Preço ➔
                                </a>
                                <div style="display: flex; gap: 6px;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-recipes&edit_id=' . $rId)); ?>" class="button button-small">✏️ Editar</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_delete_recipe&id=' . $rId), 'km_delete_recipe_' . $rId)); ?>" 
                                       class="button button-small" style="color: #b32d2e;" onclick="return confirm('Deseja excluir permanentemente esta ficha técnica?');">🗑️ Excluir</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
/* Design System Core - Kitchen Manager */
.km-admin-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.km-header-bar { background: #ffffff; padding: 18px 24px; border: 1px solid #ccd0d4; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-page-title { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; font-weight: 600; }
.km-page-desc { margin: 0; color: #646970; font-size: 14px; }
.km-notice { margin-top: 15px; margin-bottom: 20px; border-radius: 4px; border-left-width: 4px; }

/* Grid e Cards */
.km-split-grid { display: grid; grid-template-columns: 460px 1fr; gap: 24px; align-items: start; }
.km-card { background: #ffffff; border: 1px solid #ccd0d4; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden; }
.km-card-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f1; background: #fcfcfc; }
.km-card-title { font-size: 15px; margin: 0; color: #1d2327; font-weight: 600; }

/* Formulário e Inputs */
.km-field-group { margin-bottom: 16px; }
.km-field-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #2c3338; }
.km-input, .km-select { padding: 8px 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; box-sizing: border-box; }
.km-input:focus, .km-select:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.km-btn-save { height: 42px; font-size: 14px; font-weight: 600; }
.km-section-subhead { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f1; padding-top: 16px; margin-top: 8px; margin-bottom: 12px; }

/* Tooltips */
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

/* Container de Insumos */
.km-items-container { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.km-recipe-item-card { background: #fdfdfd; border: 1px solid #ccd0d4; border-radius: 6px; padding: 14px; position: relative; }
.km-item-del-btn { position: absolute; top: 10px; right: 10px; background: none; border: none; color: #b32d2e; font-weight: bold; cursor: pointer; font-size: 14px; padding: 4px; }
.km-item-del-btn:hover { background: #f8d7da; border-radius: 4px; }
.km-btn-link-del { background: none; border: none; color: #b32d2e; cursor: pointer; font-size: 12px; text-decoration: underline; padding: 0; }
.km-item-field { margin-bottom: 10px; padding-right: 24px; }
.km-item-row-flex { display: flex; justify-content: space-between; align-items: flex-end; gap: 10px; }
.km-qty-box { flex: 1; }
.km-inline-inputs { display: flex; gap: 6px; align-items: center; margin-top: 4px; }
.km-input-qty { width: 90px !important; text-align: center; font-weight: 600; }
.km-select-measure { flex: 1; }
.km-subtotal-box { text-align: right; min-width: 90px; }
.km-subtotal-val { display: block; font-size: 14px; color: #1d2327; margin-top: 4px; }

/* Totals Banner */
.km-totals-banner { background: #f0f6fc; border-left: 4px solid #2271b1; padding: 14px; border-radius: 0 4px 4px 0; margin-top: 8px; }

/* Lista de Fichas (Direita) */
.km-cards-stack { display: flex; flex-direction: column; gap: 14px; }
.km-recipe-card { background: #ffffff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.km-rc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f0f0f1; padding-bottom: 12px; }
.km-rc-title { margin: 0 0 4px 0; font-size: 16px; color: #1d2327; font-weight: 600; }
.km-rc-sub { font-size: 12px; color: #646970; }
.km-rc-cost-block { text-align: right; }
.km-rc-cost-label { display: block; font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600; }
.km-rc-cost-val { display: block; font-size: 18px; color: #2271b1; font-weight: bold; margin-top: 2px; }
.km-rc-composition { padding: 14px 0; }
.km-rc-comp-title { display: block; font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 700; margin-bottom: 8px; }
.km-tags-flex { display: flex; flex-wrap: wrap; gap: 6px; }
.km-tag-pill { background: #f6f7f7; border: 1px solid #dcdcde; padding: 4px 10px; border-radius: 12px; font-size: 12px; color: #2c3338; display: inline-flex; align-items: center; gap: 6px; }
.km-tag-del { color: #b32d2e; text-decoration: none; font-weight: bold; font-size: 11px; }
.km-rc-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f1; padding-top: 12px; margin-top: 4px; }
.km-btn-blue { border-color: #2271b1 !important; color: #2271b1 !important; font-weight: 600; }
.km-empty-box { background: #fdfdfd; border: 1px dashed #c3c4c7; padding: 40px 20px; text-align: center; border-radius: 8px; color: #646970; }

@media (max-width: 960px) {
    .km-split-grid { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container   = document.getElementById('km-recipe-items-box');
    const addBtn      = document.getElementById('km-add-ing-btn');
    const clearAllBtn = document.getElementById('km-clear-all-ings-btn');
    const yieldInp    = document.getElementById('km-r-yield');
    const batchLbl    = document.getElementById('km-lbl-batch-cost');
    const unitLbl     = document.getElementById('km-lbl-unit-cost');
    const form        = document.getElementById('km-recipe-form');

    // Bloqueia o "Enter" para não salvar a ficha técnica acidentalmente
    form.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
            event.preventDefault();
        }
    });

    function syncMeasureOptions(card) {
        const sel = card.querySelector('.km-select-ing');
        const measureSel = card.querySelector('.km-select-measure');
        if (!sel || !measureSel) return;

        //const currentVal = measureSel.getAttribute('data-current-measure') || measureSel.value;
        const currentVal = (measureSel.getAttribute('data-current-measure') || measureSel.value || '').toLowerCase().trim();

        if (sel.selectedIndex > 0) {
            const opt     = sel.options[sel.selectedIndex];
            const unit    = (opt.getAttribute('data-unit') || 'g').toLowerCase().trim();
            const pkgType = opt.getAttribute('data-pkg-type') || 'Embalagem';
            const pkgSize = opt.getAttribute('data-pkg-size') || '1';

            let optionsHtml = '';
            if (unit === 'kg' || unit === 'g') {
                optionsHtml += `<option value="g">g (gramas)</option>`;
                optionsHtml += `<option value="kg">kg (quilos)</option>`;
            } else if (unit === 'l' || unit === 'ml') {
                optionsHtml += `<option value="ml">ml (mililitros)</option>`;
                optionsHtml += `<option value="l">L (litros)</option>`;
            } else {
                optionsHtml += `<option value="${unit}">${unit}</option>`;
            }
            optionsHtml += `<option value="pkg">${pkgType} (${pkgSize} ${unit})</option>`;

            measureSel.innerHTML = optionsHtml;

            const hasOption = Array.from(measureSel.options).some(o => o.value === currentVal);
            if (hasOption) {
                measureSel.value = currentVal;
            } else {
                measureSel.value = unit;
            }
            measureSel.setAttribute('data-current-measure', measureSel.value);
        }
    }

    function calculate() {
        let total = 0;
        const yieldVal = parseFloat(yieldInp.value) || 1;

        container.querySelectorAll('.km-recipe-item-card').forEach(card => {
            const sel = card.querySelector('.km-select-ing');
            const qty = parseFloat(card.querySelector('.km-input-qty').value) || 0;
            const measureSel = card.querySelector('.km-select-measure');
            const subtotalLbl = card.querySelector('.km-subtotal-val');

            if (sel && sel.selectedIndex > 0) {
                const opt = sel.options[sel.selectedIndex];
                const unitCost = parseFloat(opt.getAttribute('data-unit-cost')) || 0; 
                const pkgCost  = parseFloat(opt.getAttribute('data-pkg-cost')) || 0;
                const baseUnit = (opt.getAttribute('data-unit') || 'g').toLowerCase().trim();
                const measureType = measureSel ? measureSel.value : baseUnit;

                let subtotal = 0;
                if (measureType === 'pkg') {
                    subtotal = pkgCost * qty;
                } else if (
                    (baseUnit === 'g' && measureType === 'kg') ||
                    (baseUnit === 'ml' && measureType === 'l')
                ) {
                    subtotal = (unitCost * 1000) * qty;
                } else if (
                    (baseUnit === 'kg' && measureType === 'g') ||
                    (baseUnit === 'l' && measureType === 'ml')
                ) {
                    subtotal = (unitCost / 1000) * qty;
                } else {
                    subtotal = unitCost * qty;
                }

                total += subtotal;
                if (subtotalLbl) {
                    subtotalLbl.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
                }
            } else if (subtotalLbl) {
                subtotalLbl.textContent = 'R$ 0,00';
            }
        });

        const unitCost = yieldVal > 0 ? (total / yieldVal) : 0;
        batchLbl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        unitLbl.textContent = 'R$ ' + unitCost.toFixed(2).replace('.', ',');
    }

    function bindCard(card) {
        const sel = card.querySelector('.km-select-ing');
        const measureSel = card.querySelector('.km-select-measure');

        if (sel) {
            sel.addEventListener('change', function () {
                syncMeasureOptions(card);
                calculate();
            });
        }

        if (measureSel) {
            measureSel.addEventListener('change', function () {
                measureSel.setAttribute('data-current-measure', this.value);
                calculate();
            });
        }

        card.querySelectorAll('input').forEach(el => {
            el.addEventListener('input', calculate);
        });

        const del = card.querySelector('.km-item-del-btn');
        if (del) {
            del.addEventListener('click', function () {
                card.remove();
                calculate();
            });
        }

        if (sel && sel.selectedIndex > 0) {
            syncMeasureOptions(card);
        }
    }

    container.querySelectorAll('.km-recipe-item-card').forEach(bindCard);
    yieldInp.addEventListener('input', calculate);

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const idx = 'item_' + Date.now();
            const div = document.createElement('div');
            div.className = 'km-recipe-item-card';
            div.innerHTML = `
                <button type="button" class="km-item-del-btn" title="Remover Insumo">✕</button>
                <div class="km-item-field">
                    <label style="font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600;">Insumo:</label>
                    <select name="items[${idx}][ingredient_id]" class="km-select widefat km-select-ing" required>
                        <option value="">-- Escolha um insumo --</option>
                        <?php foreach ($ingredients as $ing): 
                            $pkgSizeItem = (float) km_get_prop($ing, ['package_size', 'packageSize', 'package_quantity'], 1.0);
                            $pkgCostItem = (float) km_get_prop($ing, ['package_cost', 'packageCost', 'cost'], 0.0);
                            $pkgUnitItem = strtolower((string)km_get_prop($ing, ['package_unit'], 'g'));
                            $unitItem    = strtolower((string)km_get_prop($ing, ['unit', 'usage_unit', 'usageUnit'], 'g'));
                            $pkgTypeItem = km_get_prop($ing, ['package_type'], 'Embalagem');
                            $ingIdItem   = (int) km_get_prop($ing, ['id'], 0);

                            $effectiveSize = $pkgSizeItem;
                            if ($pkgUnitItem === 'kg' && $unitItem === 'g') $effectiveSize = $pkgSizeItem * 1000;
                            elseif ($pkgUnitItem === 'l' && $unitItem === 'ml') $effectiveSize = $pkgSizeItem * 1000;
                            elseif ($pkgUnitItem === 'g' && $unitItem === 'kg') $effectiveSize = $pkgSizeItem / 1000;
                            elseif ($pkgUnitItem === 'ml' && $unitItem === 'l') $effectiveSize = $pkgSizeItem / 1000;

                            $unitCostItem = ($effectiveSize > 0) ? ($pkgCostItem / $effectiveSize) : 0.0;
                        ?>
                            <option value="<?php echo esc_attr($ingIdItem); ?>" 
                                    data-unit-cost="<?php echo esc_attr($unitCostItem); ?>"
                                    data-pkg-cost="<?php echo esc_attr($pkgCostItem); ?>"
                                    data-pkg-size="<?php echo esc_attr($pkgSizeItem); ?>"
                                    data-unit="<?php echo esc_attr($unitItem); ?>"
                                    data-pkg-type="<?php echo esc_attr($pkgTypeItem); ?>">
                                <?php echo esc_html(km_get_prop($ing, ['name'], 'Insumo')); ?> (R$ <?php echo number_format($unitCostItem, 4, ',', '.'); ?>/<?php echo esc_html($unitItem); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="km-item-row-flex">
                    <div class="km-qty-box">
                        <label style="font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600;">Qtd e Medida:</label>
                        <div class="km-inline-inputs">
                            <input type="number" name="items[${idx}][quantity]" value="1" step="any" min="0.0001" class="km-input km-input-qty" required>
                            <select name="items[${idx}][measure_type]" class="km-select km-select-measure">
                                <option value="g">g</option>
                            </select>
                        </div>
                    </div>
                    <div class="km-subtotal-box">
                        <span style="font-size: 11px; text-transform: uppercase; color: #646970; font-weight: 600;">Subtotal:</span>
                        <strong class="km-subtotal-val">R$ 0,00</strong>
                    </div>
                </div>
            `;
            container.prepend(div);
            bindCard(div);
            calculate();

            const select = div.querySelector('.km-select-ing');
            if (select) select.focus();
        });
    }

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            if (confirm('Deseja remover todos os insumos desta receita?')) {
                container.innerHTML = '';
                calculate();
            }
        });
    }

    calculate();
});
</script>