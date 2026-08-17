<?php
if (!defined('ABSPATH')) exit;

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $productId > 0 ? $this->service->getProduct($productId) : null;
$isEditing = !empty($product);

// FILTRO: Apenas insumos que não são alimentos (embalagens, tags, flores)
$packagingIngredients = array_filter($ingredients, function($ing) {
    $cat = $ing->category ?? 'food';
    return $cat !== 'food';
});

$currentRole   = $isEditing ? ($product->strategic_role ?? 'anchor') : 'anchor';
$currentTarget = $isEditing ? (float)($product->target_margin ?? 30) : 30.0;
$currentPrice  = $isEditing ? (float)($product->sale_price ?? 0) : 0.0;
?>

<div class="wrap km-products-wrap">
    <div class="km-header-bar">
        <h1 style="margin: 0; font-size: 22px; color: #1d2327;">🎁 <?php echo $isEditing ? 'Editar Produto Comercial / Kit' : 'Novo Produto Comercial / Kit'; ?></h1>
        <p style="margin: 4px 0 0 0; color: #646970;">Formate a composição física e culinária deste item para determinar seu custo real e margem de venda.</p>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-product-form">
        <input type="hidden" name="action" value="km_save_product">
        <input type="hidden" name="id" value="<?php echo $isEditing ? (int)$product->id : 0; ?>">
        <?php wp_nonce_field('km_save_product_action', 'km_product_nonce'); ?>

        <div class="km-product-layout">
            
            <!-- COLUNA PRINCIPAL: COMPOSIÇÃO -->
            <div class="km-form-main">
                
                <!-- 1. IDENTIFICAÇÃO -->
                <div class="km-box">
                    <h3 class="km-box-title">1. Identificação do Produto</h3>
                    <div class="km-field" style="margin-top: 10px;">
                        <label for="km-p-name">Nome do Produto Comercial:</label>
                        <input type="text" id="km-p-name" name="name" value="<?php echo $isEditing ? esc_attr($product->name) : ''; ?>" placeholder="Ex: Caixa Presente com 6 Brigadeiros Gourmet" required class="widefat" style="height: 38px; font-size: 14px;">
                        <small>Como este item será apresentado ao cliente no cardápio ou delivery.</small>
                    </div>
                </div>

                <!-- 2. FICHAS CULINÁRIAS -->
                <div class="km-box">
                    <div class="km-box-header-flex">
                        <h3 class="km-box-title">🍳 2. Fichas Técnicas Integradas (Receitas)</h3>
                        <button type="button" class="button button-secondary button-small" id="km-add-recipe-btn">➕ Adicionar Receita</button>
                    </div>
                    <p class="km-box-desc">Selecione as receitas e quantas porções/unidades compõem este produto.</p>
                    
                    <div id="km-recipes-list" class="km-items-stack">
                        <?php if ($isEditing && !empty($product->items)): ?>
                            <?php foreach ($product->items as $idx => $it): ?>
                                <?php if ($it->item_type === 'recipe'): ?>
                                    <div class="km-comp-row">
                                        <input type="hidden" name="items[<?php echo $idx; ?>][item_type]" value="recipe">
                                        <select name="items[<?php echo $idx; ?>][item_id]" class="km-select-recipe widefat" required>
                                            <option value="">-- Selecione a Receita --</option>
                                            <?php foreach ($recipes as $r): 
                                                $uCost = (float)($r->calculated_unit_cost ?? $r->unitCost ?? 0);
                                            ?>
                                                <option value="<?php echo esc_attr($r->id); ?>" data-cost="<?php echo esc_attr($uCost); ?>" <?php selected($it->item_id, $r->id); ?>>
                                                    <?php echo esc_html($r->name); ?> (Custo Unit: R$ <?php echo number_format($uCost, 2, ',', '.'); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.1" min="0.1" name="items[<?php echo $idx; ?>][quantity]" value="<?php echo esc_attr($it->quantity); ?>" class="km-qty-input" required>
                                        <span class="km-row-unit">porções</span>
                                        <strong class="km-row-subtotal">R$ <?php echo number_format($it->total_cost, 2, ',', '.'); ?></strong>
                                        <button type="button" class="km-del-row-btn" title="Remover">✕</button>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. EMBALAGENS E ACESSÓRIOS -->
                <div class="km-box">
                    <div class="km-box-header-flex">
                        <h3 class="km-box-title">📦 3. Embalagens, Flores & Finalização</h3>
                        <button type="button" class="button button-secondary button-small" id="km-add-pack-btn">➕ Adicionar Embalagem / Flor</button>
                    </div>
                    <p class="km-box-desc">Insumos físicos que acompanham este produto (caixas, laços, tags, flores).</p>
                    
                    <div id="km-pack-list" class="km-items-stack">
                        <?php if ($isEditing && !empty($product->items)): ?>
                            <?php foreach ($product->items as $idx => $it): ?>
                                <?php if ($it->item_type === 'ingredient'): ?>
                                    <div class="km-comp-row">
                                        <input type="hidden" name="items[<?php echo $idx; ?>][item_type]" value="ingredient">
                                        <select name="items[<?php echo $idx; ?>][item_id]" class="km-select-pack widefat" required>
                                            <option value="">-- Selecione a Embalagem/Acessório --</option>
                                            <?php foreach ($packagingIngredients as $ing): 
                                                $uCost = (float)($ing->calculated_unit_cost ?? $ing->unit_cost ?? 0);
                                                $catName = strtoupper(esc_html($ing->category ?? 'EMBALAGEM'));
                                            ?>
                                                <option value="<?php echo esc_attr($ing->id); ?>" data-cost="<?php echo esc_attr($uCost); ?>" <?php selected($it->item_id, $ing->id); ?>>
                                                    <?php echo esc_html($ing->name); ?> (R$ <?php echo number_format($uCost, 2, ',', '.'); ?>/<?php echo esc_html($ing->unit ?? 'un'); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.1" min="0.1" name="items[<?php echo $idx; ?>][quantity]" value="<?php echo esc_attr($it->quantity); ?>" class="km-qty-input" required>
                                        <span class="km-row-unit"><?php echo esc_html($it->unit ?? 'un'); ?></span>
                                        <strong class="km-row-subtotal">R$ <?php echo number_format($it->total_cost, 2, ',', '.'); ?></strong>
                                        <button type="button" class="km-del-row-btn" title="Remover">✕</button>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. ESTRATÉGIA DE MERCADO & PREÇO (BOTÕES + SLIDERS) -->
                <div class="km-box">
                    <h3 class="km-box-title">🎯 4. Estratégia de Mercado & Preço</h3>
                    <p class="km-box-desc">Escolha o papel do produto na sua esteira de vendas e ajuste os sliders para simular o preço e o lucro desejado.</p>
                    
                    <!-- BOTÕES DE PAPEL ESTRATÉGICO COM DESCRIÇÃO -->
                    <div class="km-field" style="margin-top: 14px;">
                        <label style="font-weight: 700; font-size: 13px; color: #1d2327; margin-bottom: 8px;">Papel Estratégico do Produto:</label>
                        <div class="km-role-cards-grid">
                            <?php foreach (\KitchenManager\Modules\Products\Services\ProductService::ROLES as $rKey => $rData): 
                                $isChecked = ($currentRole === $rKey);
                            ?>
                                <label class="km-role-card <?php echo $isChecked ? 'is-active' : ''; ?>">
                                    <input type="radio" name="strategic_role" value="<?php echo esc_attr($rKey); ?>" 
                                           data-target="<?php echo esc_attr($rData['suggested_margin']); ?>" 
                                           <?php checked($isChecked); ?> class="km-role-radio">
                                    <div class="km-role-card-header">
                                        <strong class="km-role-card-title"><?php echo esc_html($rData['label']); ?></strong>
                                        <span class="km-role-card-badge">Meta: <?php echo $rData['suggested_margin']; ?>%</span>
                                    </div>
                                    <p class="km-role-card-desc"><?php echo esc_html($rData['desc']); ?></p>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- SLIDERS DE MARGEM E PREÇO -->
                    <div class="km-sliders-grid" style="margin-top: 18px;">
                        
                        <!-- SLIDER 1: META DE MARGEM -->
                        <div class="km-slider-control-box">
                            <div class="km-slider-header">
                                <label for="km-target-margin" style="font-weight: 700; font-size: 13px; color: #2c3338;">Meta de Margem Alvo (%):</label>
                                <div class="km-slider-val-tag" id="km-tag-target-margin"><?php echo $currentTarget; ?>%</div>
                            </div>
                            <input type="range" min="5" max="85" step="1" id="km-target-margin-range" value="<?php echo esc_attr($currentTarget); ?>" class="km-range-slider">
                            <div class="km-slider-ticks">
                                <span>5% (Volume)</span>
                                <span>30% (Padrão)</span>
                                <span>60%+ (Premium)</span>
                            </div>
                            <input type="hidden" id="km-target-margin" name="target_margin" value="<?php echo esc_attr($currentTarget); ?>">
                        </div>

                        <!-- SLIDER 2: PREÇO DE VENDA PRATICADO -->
                        <div class="km-slider-control-box">
                            <div class="km-slider-header">
                                <label for="km-sale-price-inp" style="font-weight: 700; font-size: 13px; color: #2c3338;">Preço de Venda Praticado (R$):</label>
                                <div class="km-price-input-wrapper">
                                    <span style="font-weight: 700; color: #2271b1; font-size: 14px;">R$</span>
                                    <input type="number" step="0.50" min="0" id="km-sale-price-inp" value="<?php echo esc_attr($currentPrice); ?>" class="km-price-number-input">
                                </div>
                            </div>
                            <input type="range" min="0" max="300" step="0.50" id="km-sale-price-range" value="<?php echo esc_attr($currentPrice); ?>" class="km-range-slider">
                            <div class="km-slider-ticks">
                                <span>R$ 0,00</span>
                                <span id="km-tick-sugg-price">Preço Sugerido</span>
                                <span>R$ 300,00+</span>
                            </div>
                            <input type="hidden" id="km-sale-price" name="sale_price" value="<?php echo esc_attr($currentPrice); ?>">
                        </div>

                    </div>

                </div>

            </div>

            <!-- LATERAL: RESUMO E MARGEM -->
            <div class="km-form-sidebar">
                <div class="km-card km-sticky-summary">
                    <h3 style="margin: 0 0 12px 0; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px;">📊 Resumo de Custos & Margem</h3>

                    <div class="km-summary-line">
                        <span>Custo das Fichas Culinárias:</span>
                        <strong id="km-sum-recipes">R$ 0,00</strong>
                    </div>
                    <div class="km-summary-line">
                        <span>Embalagens & Acessórios:</span>
                        <strong id="km-sum-pack">R$ 0,00</strong>
                    </div>
                    <div class="km-summary-line km-total-highlight">
                        <span>Custo Direto Total:</span>
                        <strong id="km-sum-total">R$ 0,00</strong>
                    </div>

                    <div id="km-margin-card" class="km-margin-status-box">
                        <span id="km-margin-badge" class="km-status-badge">AGUARDANDO PREÇO</span>
                        <div style="font-size: 28px; font-weight: 800; margin: 6px 0;" id="km-margin-pct">0.0%</div>
                        <small id="km-margin-text">Defina o preço de venda para calcular.</small>
                    </div>

                    <div style="margin-top: 18px;">
                        <button type="submit" class="button button-primary button-large" style="width: 100%; height: 44px; font-size: 15px; font-weight: 600;">
                            💾 <?php echo $isEditing ? 'Salvar Alterações' : 'Criar Produto Comercial'; ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-products')); ?>" class="button button-secondary" style="width: 100%; text-align: center; margin-top: 8px; height: 36px; line-height: 34px;">
                            ← Voltar para Lista
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<style>
.km-products-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.km-header-bar { background: #ffffff; padding: 16px 20px; border: 1px solid #ccd0d4; border-radius: 8px; margin-bottom: 18px; }
.km-product-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.km-box { background: #fff; border: 1px solid #ccd0d4; padding: 18px 20px; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.km-box-title { font-size: 15px; margin: 0; color: #1d2327; font-weight: 700; }
.km-box-desc { font-size: 12px; color: #646970; margin: 4px 0 12px 0; }
.km-box-header-flex { display: flex; justify-content: space-between; align-items: center; }
.km-field { margin-bottom: 12px; }
.km-field label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px; color: #2c3338; }
.km-field small { display: block; color: #646970; font-size: 12px; margin-top: 3px; }

/* GRADE DE BOTÕES DE PAPEL ESTRATÉGICO */
.km-role-cards-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 6px; }
.km-role-card { background: #fdfdfd; border: 1.5px solid #dcdcde; border-radius: 8px; padding: 12px 14px; cursor: pointer; transition: all 0.2s ease; position: relative; display: block; }
.km-role-card:hover { border-color: #2271b1; background: #f6f7f7; }
.km-role-card.is-active { border-color: #2271b1; background: #f0f6fc; box-shadow: 0 0 0 1px #2271b1; }
.km-role-radio { position: absolute; opacity: 0; pointer-events: none; }
.km-role-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
.km-role-card-title { font-size: 13px; color: #1d2327; }
.km-role-card-badge { background: #e7f3ff; color: #1d6ca5; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 12px; }
.km-role-card.is-active .km-role-card-badge { background: #2271b1; color: #fff; }
.km-role-card-desc { font-size: 11.5px; color: #646970; margin: 0; line-height: 1.4; }

/* SLIDERS MODERNOS */
.km-sliders-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.km-slider-control-box { background: #fcfcfc; border: 1px solid #e2e4e7; border-radius: 8px; padding: 14px 16px; }
.km-slider-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.km-slider-val-tag { background: #2271b1; color: #fff; font-weight: 800; font-size: 13px; padding: 3px 9px; border-radius: 6px; }
.km-price-input-wrapper { display: flex; align-items: center; gap: 4px; }
.km-price-number-input { width: 90px !important; height: 32px; font-weight: 800; font-size: 14px; text-align: right; color: #2271b1; border: 1px solid #8c8f94; border-radius: 4px; }

.km-range-slider { width: 100%; height: 6px; border-radius: 4px; outline: none; -webkit-appearance: none; background: #dcdcde; margin: 8px 0 4px 0; }
.km-range-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #2271b1; cursor: pointer; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
.km-range-slider::-moz-range-thumb { width: 18px; height: 18px; border-radius: 50%; background: #2271b1; cursor: pointer; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }

.km-slider-ticks { display: flex; justify-content: space-between; font-size: 10.5px; color: #8c8f94; margin-top: 4px; }

/* LINHAS DINÂMICAS DE COMPOSIÇÃO */
.km-items-stack { display: flex; flex-direction: column; gap: 8px; }
.km-comp-row { display: flex; gap: 8px; align-items: center; background: #fdfdfd; border: 1px solid #e2e4e7; padding: 8px 12px; border-radius: 6px; }
.km-qty-input { width: 85px !important; text-align: center; height: 34px; font-weight: 600; }
.km-row-unit { font-size: 12px; color: #646970; min-width: 50px; }
.km-row-subtotal { min-width: 90px; text-align: right; font-size: 13px; color: #1d2327; }
.km-del-row-btn { background: none; border: none; color: #b32d2e; font-weight: bold; cursor: pointer; font-size: 15px; padding: 4px 8px; }

/* CARD LATERAL */
.km-card { background: #fff; border: 1px solid #ccd0d4; padding: 18px; border-radius: 8px; }
.km-sticky-summary { position: sticky; top: 40px; }
.km-summary-line { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; color: #50575e; }
.km-total-highlight { border-top: 1px solid #eee; padding-top: 8px; font-size: 15px; font-weight: 700; color: #1d2327; }
.km-margin-status-box { background: #f6f7f7; border: 1px solid #c3c4c7; padding: 14px; border-radius: 6px; text-align: center; margin-top: 15px; }
.km-status-badge { font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const recipesBox      = document.getElementById('km-recipes-list');
    const packBox         = document.getElementById('km-pack-list');
    const addRecBtn       = document.getElementById('km-add-recipe-btn');
    const addPackBtn      = document.getElementById('km-add-pack-btn');

    // Papel Estratégico Cards
    const roleCards       = document.querySelectorAll('.km-role-card');
    const roleRadios      = document.querySelectorAll('.km-role-radio');

    // Sliders & Inputs
    const targetRange     = document.getElementById('km-target-margin-range');
    const targetTag       = document.getElementById('km-tag-target-margin');
    const targetHidden    = document.getElementById('km-target-margin');

    const priceRange      = document.getElementById('km-sale-price-range');
    const priceNumberInp  = document.getElementById('km-sale-price-inp');
    const priceHidden     = document.getElementById('km-sale-price');
    const tickSuggPrice   = document.getElementById('km-tick-sugg-price');

    // Sidebar
    const sumRecLbl       = document.getElementById('km-sum-recipes');
    const sumPackLbl      = document.getElementById('km-sum-pack');
    const sumTotLbl       = document.getElementById('km-sum-total');
    const margPctLbl      = document.getElementById('km-margin-pct');
    const margCard        = document.getElementById('km-margin-card');
    const margBadge       = document.getElementById('km-margin-badge');
    const margText        = document.getElementById('km-margin-text');

    let currentTotalCost  = 0;

    // 1. SELEÇÃO DE PAPEL ESTRATÉGICO VIA BOTÕES
    roleRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            roleCards.forEach(c => c.classList.remove('is-active'));
            this.closest('.km-role-card').classList.add('is-active');

            const suggested = parseFloat(this.getAttribute('data-target')) || 30;
            targetRange.value = suggested;
            targetTag.textContent = suggested + '%';
            targetHidden.value = suggested;

            // Recalcula o preço sugerido com base no custo atual e na nova meta
            if (currentTotalCost > 0 && suggested < 100) {
                const suggPrice = currentTotalCost / (1 - (suggested / 100));
                setSalePrice(suggPrice);
            }
            calculate();
        });
    });

    // 2. SINCRONIZAÇÃO SLIDER DE MARGEM ALVO
    targetRange.addEventListener('input', function () {
        const val = this.value;
        targetTag.textContent = val + '%';
        targetHidden.value = val;
        calculate();
    });

    // 3. SINCRONIZAÇÃO SLIDER DE PREÇO DE VENDA
    function setSalePrice(val) {
        val = Math.max(0, parseFloat(val) || 0);
        
        // Ajusta o max do slider se o preço ultrapassar 300
        if (val > parseFloat(priceRange.max)) {
            priceRange.max = Math.ceil(val * 1.3);
        }

        priceRange.value = val;
        priceNumberInp.value = val.toFixed(2);
        priceHidden.value = val.toFixed(2);
    }

    priceRange.addEventListener('input', function () {
        const val = parseFloat(this.value) || 0;
        priceNumberInp.value = val.toFixed(2);
        priceHidden.value = val.toFixed(2);
        calculate();
    });

    priceNumberInp.addEventListener('input', function () {
        const val = parseFloat(this.value) || 0;
        setSalePrice(val);
        calculate();
    });

    // 4. CÁLCULO GERAL DINÂMICO
    function calculate() {
        let recTotal = 0;
        let packTotal = 0;

        recipesBox.querySelectorAll('.km-comp-row').forEach(row => {
            const sel = row.querySelector('select');
            const qty = parseFloat(row.querySelector('input[type="number"]').value) || 0;
            const subLbl = row.querySelector('.km-row-subtotal');
            
            if (sel && sel.selectedIndex > 0) {
                const cost = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-cost')) || 0;
                const sub = cost * qty;
                recTotal += sub;
                if (subLbl) subLbl.textContent = 'R$ ' + sub.toFixed(2).replace('.', ',');
            }
        });

        packBox.querySelectorAll('.km-comp-row').forEach(row => {
            const sel = row.querySelector('select');
            const qty = parseFloat(row.querySelector('input[type="number"]').value) || 0;
            const subLbl = row.querySelector('.km-row-subtotal');
            
            if (sel && sel.selectedIndex > 0) {
                const cost = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-cost')) || 0;
                const sub = cost * qty;
                packTotal += sub;
                if (subLbl) subLbl.textContent = 'R$ ' + sub.toFixed(2).replace('.', ',');
            }
        });

        currentTotalCost = recTotal + packTotal;
        const targetMargin = parseFloat(targetHidden.value) || 30;
        const salePrice = parseFloat(priceHidden.value) || 0;

        sumRecLbl.textContent  = 'R$ ' + recTotal.toFixed(2).replace('.', ',');
        sumPackLbl.textContent = 'R$ ' + packTotal.toFixed(2).replace('.', ',');
        sumTotLbl.textContent  = 'R$ ' + currentTotalCost.toFixed(2).replace('.', ',');

        // Atualiza a dica de preço sugerido no tick
        if (currentTotalCost > 0 && targetMargin < 100) {
            const sugg = currentTotalCost / (1 - (targetMargin / 100));
            tickSuggPrice.textContent = 'Sugerido: R$ ' + sugg.toFixed(2).replace('.', ',');
        } else {
            tickSuggPrice.textContent = 'Preço Sugerido';
        }

        // Análise de Margem no Card Lateral
        if (salePrice > 0) {
            const margin = ((salePrice - currentTotalCost) / salePrice) * 100;
            margPctLbl.textContent = margin.toFixed(1) + '%';

            if (margin >= targetMargin) {
                margCard.style.background = '#edf7ed';
                margCard.style.borderColor = '#4caf50';
                margBadge.style.color = '#2e7d32';
                margBadge.textContent = 'MARGEM SAUDÁVEL';
                margText.textContent = `Lucro Bruto: R$ ${(salePrice - currentTotalCost).toFixed(2).replace('.', ',')} por unidade.`;
            } else if (margin > 0) {
                margCard.style.background = '#fff4e5';
                margCard.style.borderColor = '#ff9800';
                margBadge.style.color = '#ed6c02';
                margBadge.textContent = 'ABAIXO DA META';
                margText.textContent = `Abaixo da meta de ${targetMargin}%. Ajuste o slider de preço.`;
            } else {
                margCard.style.background = '#fdeded';
                margCard.style.borderColor = '#ef5350';
                margBadge.style.color = '#c62828';
                margBadge.textContent = 'PREJUÍZO DIRETO';
                margText.textContent = 'O custo direto supera o preço praticado!';
            }
        } else {
            margPctLbl.textContent = '0.0%';
            margCard.style.background = '#f6f7f7';
            margCard.style.borderColor = '#c3c4c7';
            margBadge.style.color = '#646970';
            margBadge.textContent = 'AGUARDANDO PREÇO';
            margText.textContent = 'Mova o slider de preço para simular.';
        }
    }

    function bindRow(row) {
        row.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', calculate);
            el.addEventListener('change', calculate);
        });
        const del = row.querySelector('.km-del-row-btn');
        if (del) {
            del.addEventListener('click', () => { 
                row.remove(); 
                calculate(); 
            });
        }
    }

    document.querySelectorAll('.km-comp-row').forEach(bindRow);

    if (addRecBtn) {
        addRecBtn.addEventListener('click', function () {
            const idx = Date.now();
            const div = document.createElement('div');
            div.className = 'km-comp-row';
            div.innerHTML = `
                <input type="hidden" name="items[${idx}][item_type]" value="recipe">
                <select name="items[${idx}][item_id]" class="km-select-recipe widefat" required>
                    <option value="">-- Selecione a Receita --</option>
                    <?php foreach ($recipes as $r): 
                        $uCost = (float)($r->calculated_unit_cost ?? $r->unitCost ?? 0);
                    ?>
                        <option value="<?php echo esc_attr($r->id); ?>" data-cost="<?php echo esc_attr($uCost); ?>">
                            <?php echo esc_html($r->name); ?> (Custo Unit: R$ <?php echo number_format($uCost, 2, ',', '.'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.1" min="0.1" name="items[${idx}][quantity]" value="1" class="km-qty-input" required>
                <span class="km-row-unit">porções</span>
                <strong class="km-row-subtotal">R$ 0,00</strong>
                <button type="button" class="km-del-row-btn" title="Remover">✕</button>
            `;
            recipesBox.appendChild(div);
            bindRow(div);
            calculate();
        });
    }

    if (addPackBtn) {
        addPackBtn.addEventListener('click', function () {
            const idx = Date.now();
            const div = document.createElement('div');
            div.className = 'km-comp-row';
            div.innerHTML = `
                <input type="hidden" name="items[${idx}][item_type]" value="ingredient">
                <select name="items[${idx}][item_id]" class="km-select-pack widefat" required>
                    <option value="">-- Selecione a Embalagem/Acessório --</option>
                    <?php foreach ($packagingIngredients as $ing): 
                        $uCost = (float)($ing->calculated_unit_cost ?? $ing->unit_cost ?? 0);
                        $catName = strtoupper(esc_html($ing->category ?? 'EMBALAGEM'));
                    ?>
                        <option value="<?php echo esc_attr($ing->id); ?>" data-cost="<?php echo esc_attr($uCost); ?>">
                            <?php echo esc_html($ing->name); ?> (R$ <?php echo number_format($uCost, 2, ',', '.'); ?>/<?php echo esc_html($ing->unit ?? 'un'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.1" min="0.1" name="items[${idx}][quantity]" value="1" class="km-qty-input" required>
                <span class="km-row-unit">un</span>
                <strong class="km-row-subtotal">R$ 0,00</strong>
                <button type="button" class="km-del-row-btn" title="Remover">✕</button>
            `;
            packBox.appendChild(div);
            bindRow(div);
            calculate();
        });
    }

    calculate();
});
</script>