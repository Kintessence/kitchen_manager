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

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-ingredients-form">
        <input type="hidden" name="action" value="km_save_ingredients">
        <?php wp_nonce_field('km_save_ingredients_action', 'km_ingredients_nonce'); ?>

        <div class="km-card" style="margin-top: 15px; overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped" id="km-ingredients-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">
                            Nome do Insumo 
                            <span class="km-tooltip-icon" title="Nome comercial ou de controle do insumo (ex: Leite Condensado Moça, Farinha de Trigo, Caixa 6 Doces).">?</span>
                        </th>
                        <th style="width: 12%;">
                            Categoria 
                            <span class="km-tooltip-icon" title="Tipo do item para separação automática entre receitas culinárias e kits comerciais.">?</span>
                        </th>
                        <th style="width: 12%;">
                            Tipo de Embalagem 
                            <span class="km-tooltip-icon" title="Como você compra este item (ex: Pacote, Lata, Caixa, Rolo).">?</span>
                        </th>
                        <th style="width: 9%;">
                            Conteúdo 
                            <span class="km-tooltip-icon" title="A quantidade líquida/peso que vem dentro da embalagem fechada (ex: 395, 1, 1000).">?</span>
                        </th>
                        <th style="width: 9%;">
                            Unid. Embalagem 
                            <span class="km-tooltip-icon" title="Unidade de medida que vem descrita no rótulo da embalagem (ex: g, kg, ml, L, un).">?</span>
                        </th>
                        <th style="width: 10%;">
                            Custo (R$) 
                            <span class="km-tooltip-icon" title="Preço total pago pela embalagem/unidade de compra fechada.">?</span>
                        </th>
                        <th style="width: 9%;">
                            Unid. de Uso 
                            <span class="km-tooltip-icon" title="Unidade em que você costuma fracionar na receita (ex: se compra em kg, você usa em g).">?</span>
                        </th>
                        <th style="width: 11%;">
                            Custo / Uso 
                            <span class="km-tooltip-icon" title="Custo calculado automaticamente por fração que será cobrado na ficha técnica.">?</span>
                        </th>
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

                            // Cálculo inteligente de conversão
                            $effectiveSize = $pkgSize;
                            if ($pkgUnit === 'kg' && $unit === 'g') {
                                $effectiveSize = $pkgSize * 1000;
                            } elseif ($pkgUnit === 'l' && $unit === 'ml') {
                                $effectiveSize = $pkgSize * 1000;
                            }
                            $unitCost = ($effectiveSize > 0) ? ($pkgCost / $effectiveSize) : 0;
                        ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="ingredients[<?php echo $idx; ?>][id]" value="<?php echo esc_attr($ing->id); ?>">
                                    <input type="text" name="ingredients[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($ing->name); ?>" required class="widefat" placeholder="Ex: Açúcar Refinado Especial">
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

        <!-- PAINEL DE LEGENDA EXPLICATIVA DAS CATEGORIAS -->
        <div class="km-legend-container">
            <h3 class="km-legend-title">📖 Guia Rápido das Categorias de Insumo</h3>
            <div class="km-legend-grid">
                <div class="km-legend-card">
                    <strong>🍞 Ingrediente</strong>
                    <p>Alimentos comestíveis e matérias-primas que sofrem cocção ou preparo nas receitas (ex: Leite Condensado, Chocolate, Farinha, Manteiga, Frutas, Ovos).</p>
                </div>
                <div class="km-legend-card">
                    <strong>📦 Embalagem</strong>
                    <p>Itens que acondicionam os doces ou kits físicos para venda (ex: Caixa para 6 Doces, Pote de Vidro, Berço plástico, Sacola Kraft, Forminhas de papel).</p>
                </div>
                <div class="km-legend-card">
                    <strong>🏷️ Acabamento</strong>
                    <p>Materiais de fechamento, proteção e identificação visual do pacote (ex: Fita de Cetim por metro, Tag comemorativa, Adesivo lacre, Papel seda).</p>
                </div>
                <div class="km-legend-card">
                    <strong>💐 Decoração</strong>
                    <p>Elementos ornamentais que agregam alto valor estético ao produto final (ex: Flor natural avulsa, Mini buquê desidratado, Vela artesanal, Topo de bolo).</p>
                </div>
                <div class="km-legend-card">
                    <strong>✨ Outro</strong>
                    <p>Qualquer complemento, brinde ou insumo especial não alimentício adicionado à composição do pedido.</p>
                </div>
            </div>
        </div>

    </form>
</div>

<style>
.km-ingredients-wrap { max-width: 1320px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.km-header-bar { background: #ffffff; padding: 16px 20px; border: 1px solid #ccd0d4; border-radius: 8px; }
.km-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }

/* TOOLTIPS */
.km-tooltip-icon { display: inline-block; width: 15px; height: 15px; background: #e0e0e0; color: #50575e; border-radius: 50%; font-size: 10px; line-height: 15px; text-align: center; cursor: help; font-weight: bold; margin-left: 2px; }
.km-tooltip-icon:hover { background: #2271b1; color: #fff; }

/* INPUTS COMPACTOS NA TABELA */
.km-cat-sel, .km-pkg-type-sel, .km-sel-pkg-unit, .km-sel-unit { font-size: 12px; height: 32px; padding: 2px 6px; }
.km-inp-size, .km-inp-cost { font-size: 13px; height: 32px; padding: 2px 6px; text-align: center; }
.km-unit-cost-preview { font-size: 12px; color: #1d2327; }
.km-btn-del { text-decoration: none; font-size: 15px; filter: grayscale(1); transition: all 0.2s; }
.km-btn-del:hover { filter: grayscale(0); transform: scale(1.15); }

/* LEGENDA INFERIOR */
.km-legend-container { margin-top: 20px; background: #ffffff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 18px 20px; }
.km-legend-title { margin: 0 0 12px 0; font-size: 14px; color: #1d2327; font-weight: 700; }
.km-legend-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
.km-legend-card { background: #f9f9fb; border: 1px solid #e2e4e7; border-radius: 6px; padding: 10px 12px; }
.km-legend-card strong { display: block; font-size: 12px; color: #2271b1; margin-bottom: 4px; }
.km-legend-card p { margin: 0; font-size: 11.5px; color: #646970; line-height: 1.4; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('km-ingredients-rows');
    const addBtn = document.getElementById('km-add-row-btn');

    function updateRowCalc(tr) {
        const cost    = parseFloat(tr.querySelector('.km-inp-cost').value) || 0;
        const size    = parseFloat(tr.querySelector('.km-inp-size').value) || 1;
        const pkgUnit = tr.querySelector('.km-sel-pkg-unit').value || 'g';
        const unit    = tr.querySelector('.km-sel-unit').value || 'g';
        const preview = tr.querySelector('.km-unit-cost-preview');
        
        let effectiveSize = size;
        if (pkgUnit === 'kg' && unit === 'g') {
            effectiveSize = size * 1000;
        } else if (pkgUnit === 'l' && unit === 'ml') {
            effectiveSize = size * 1000;
        }

        const unitCost = effectiveSize > 0 ? (cost / effectiveSize) : 0;
        if (preview) {
            preview.textContent = 'R$ ' + unitCost.toFixed(4).replace('.', ',') + ' / ' + unit;
        }
    }

    function autoSyncUnits(tr, changedEl) {
        const pkgUnitSel = tr.querySelector('.km-sel-pkg-unit');
        const unitSel    = tr.querySelector('.km-sel-unit');

        if (changedEl === pkgUnitSel) {
            const val = pkgUnitSel.value;
            if (val === 'kg') unitSel.value = 'g';
            else if (val === 'l') unitSel.value = 'ml';
            else unitSel.value = val;
        }
    }

    function bindRow(tr) {
        tr.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', () => updateRowCalc(tr));
            el.addEventListener('change', (e) => {
                autoSyncUnits(tr, e.target);
                updateRowCalc(tr);
            });
        });
    }

    tbody.querySelectorAll('tr').forEach(bindRow);

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const idx = Date.now();
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" name="ingredients[${idx}][name]" required class="widefat" placeholder="Ex: Farinha de Trigo Especial">
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