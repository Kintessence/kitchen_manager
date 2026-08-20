<?php
if (!defined('ABSPATH')) exit;

use KitchenManager\Modules\BusinessProfile\Services\BusinessProfileService;
use KitchenManager\Modules\Recipes\Services\RecipeService;

$profileService = new BusinessProfileService();
$profile = $profileService->getProfile();

$recipeService = new RecipeService();
$recipes = $recipeService->getRecipes();

// Parâmetros via URL (se vier da listagem de fichas)
$recipeName    = isset($_GET['recipe_name']) ? sanitize_text_field(wp_unslash($_GET['recipe_name'])) : '';
$defaultCost   = isset($_GET['cost']) && (float) $_GET['cost'] > 0 ? (float) $_GET['cost'] : 0.00;
$defaultMargin = (float) ($profile['target_net_margin'] ?? 30.0);
$salesChannels = $profile['sales_channels'] ?? [
    ['name' => 'Balcão / Pix', 'tax_percent' => 0.0],
    ['name' => 'Cartão de Débito', 'tax_percent' => 1.8],
    ['name' => 'Cartão de Crédito (1x)', 'tax_percent' => 3.8],
    ['name' => 'iFood (Entrega Própria)', 'tax_percent' => 12.0],
    ['name' => 'iFood (Entrega Parceira)', 'tax_percent' => 27.0]
];
?>

<div class="wrap km-admin-wrap">
    
    <div class="km-header-bar">
        <div>
            <h1 class="km-page-title">🧭 Formação de Preço e Diagnóstico</h1>
            <p class="km-page-desc">
                Identifique custos invisíveis, aplique o 
                <span class="km-tooltip-trigger" data-tooltip="Fórmula matemática que calcula o preço de venda de cima para baixo, garantindo que as taxas do cartão/app incidam sobre o valor final sem corroer o custo ou o lucro.">Markup Divisor ℹ️</span> 
                e veja a viabilidade em múltiplos canais.
            </p>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-recipes')); ?>" class="button button-secondary">
                📋 Voltar para Fichas Técnicas
            </a>
        </div>
    </div>

    <div class="km-recipe-badge-bar">
        <div class="km-recipe-badge-info">
            <span class="km-tag-label">📋 Ficha Técnica em Análise:</span>
            <select id="km-recipe-switcher" class="km-input" style="min-width: 320px; font-weight: 600;">
                <option value="<?php echo esc_attr($defaultCost); ?>" <?php echo empty($recipeName) ? 'selected' : ''; ?>>
                    <?php echo !empty($recipeName) ? esc_html($recipeName) . ' (Custo: R$ ' . number_format($defaultCost, 2, ',', '.') . ')' : '— Simulação Manual Avulsa —'; ?>
                </option>
                <?php foreach ($recipes as $r): ?>
                    <?php if ($r->name !== $recipeName): ?>
                        <option value="<?php echo esc_attr($r->unitCost); ?>" data-name="<?php echo esc_attr($r->name); ?>">
                            <?php echo esc_html($r->name); ?> (Custo Unitário: R$ <?php echo number_format($r->unitCost, 2, ',', '.'); ?>)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="km-split-grid">
        
        <div class="km-card km-controls-col">
            
            <h2 class="km-card-title" style="margin-bottom: 16px;">1. Parâmetros de Custo e Meta</h2>

            <div class="km-field-group">
                <label for="km-direct-cost">
                    Custo Direto Unitário (Insumos) [R$]:
                    <span class="km-tooltip-trigger" data-tooltip="A soma de todos os ingredientes fracionados mais a embalagem individual do produto.">ℹ️</span>
                </label>
                <input type="number" id="km-direct-cost" step="0.01" min="0" value="<?php echo esc_attr(number_format($defaultCost, 2, '.', '')); ?>" class="km-input widefat">
                <small>Quanto custa produzir exatamente 1 porção ou unidade.</small>
            </div>

            <div class="km-field-group" style="margin-top: 14px;">
                <label for="km-target-margin">
                    Meta de Margem Líquida Desejada (%):
                    <span class="km-tooltip-trigger" data-tooltip="A porcentagem do preço de venda que fica limpa na conta bancária da empresa após pagar todos os custos e taxas.">ℹ️</span>
                </label>
                <div class="km-input-addon-group km-addon-right">
                    <input type="number" id="km-target-margin" step="0.1" min="1" max="90" value="<?php echo esc_attr($defaultMargin); ?>" class="km-input widefat">
                    <span class="km-addon">%</span>
                </div>
                <small>Definida no seu Perfil do Negócio (ajustável aqui para simulações).</small>
            </div>

            <hr class="km-divider">

            <h2 class="km-card-title" style="margin-bottom: 16px;">2. Simulação do Preço de Venda</h2>

            <div class="km-field-group">
                <label for="km-market-price">
                    Preço que você planeja cobrar [R$]:
                    <span class="km-tooltip-trigger" data-tooltip="O valor de etiqueta na sua loja física ou delivery. Ao alterar este valor, todos os canais ao lado recalculam sua viabilidade.">ℹ️</span>
                </label>
                <input type="number" id="km-market-price" step="0.01" min="0" value="0.00" class="km-input widefat" style="font-weight: bold; font-size: 18px; color: #2271b1; height: 42px;">
                <div class="km-slider-wrap">
                    <input type="range" id="km-price-slider" min="0.1" max="50" step="0.05" value="1.00" class="km-slider">
                </div>
                <small>Arraste o slider para testar preços e diagnosticar a saúde da margem em cada canal.</small>
            </div>

            <div class="km-markup-info-box">
                <strong>📐 Multiplicador de Markup Aplicado:</strong>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                    <span id="km-markup-multiplier" style="font-size: 20px; font-weight: 700; color: #1d2327;">0.00x</span>
                    <small style="max-width: 220px; text-align: right; color: #50575e; margin: 0;">
                        O preço testado é <strong id="km-markup-times">0x</strong> o custo de produção.
                    </small>
                </div>
            </div>

        </div>

        <div class="km-card km-results-col" style="background: #fdfdfd;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 class="km-card-title" style="margin: 0;">💡 Diagnóstico Multicanal em Tempo Real</h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-business-profile')); ?>" style="font-size: 12px; text-decoration: none; font-weight: 600; color: #2271b1;">⚙️ Editar Taxas</a>
            </div>
            <p style="font-size: 13px; color: #646970; margin: 0 0 16px 0;">
                Veja como o mesmo preço de <strong id="km-label-sim-price" style="color: #1d2327;">R$ 0,00</strong> se comporta em cada canal cadastrado:
            </p>

            <div class="km-channels-table-wrap">
                <table class="km-channels-table">
                    <thead>
                        <tr>
                            <th>Canal de Venda</th>
                            <th>Taxa (%)</th>
                            <th>Preço Ideal p/ Meta</th>
                            <th>Sobra em R$</th>
                            <th>Margem Real</th>
                        </tr>
                    </thead>
                    <tbody id="km-channels-tbody">
                        </tbody>
                </table>
            </div>

            <div id="km-hero-status-box" class="km-hero-card km-status-success" style="margin-top: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="km-hero-tag" id="km-hero-status-title">✅ Margem Saudável</span>
                </div>
                <p id="km-hero-status-desc" style="margin: 8px 0 0 0; font-size: 13px; line-height: 1.4;">
                    No balcão você retém a margem máxima projetada.
                </p>
            </div>

        </div>

    </div>
</div>

<script>
window.KM_SALES_CHANNELS = <?php echo json_encode($salesChannels); ?>;
</script>

<style>
/* Design System Core - Kitchen Manager */
.km-admin-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.km-header-bar { background: #ffffff; padding: 18px 24px; border: 1px solid #ccd0d4; border-radius: 8px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-page-title { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; font-weight: 600; }
.km-page-desc { margin: 0; color: #646970; font-size: 14px; }

/* Barra de Receita */
.km-recipe-badge-bar { background: #ffffff; border: 1px solid #ccd0d4; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.km-recipe-badge-info { display: flex; align-items: center; gap: 12px; }
.km-tag-label { font-weight: 700; font-size: 14px; color: #1d2327; }

/* Grid e Cards */
.km-split-grid { display: grid; grid-template-columns: 420px 1fr; gap: 24px; align-items: start; }
.km-card { background: #ffffff; border: 1px solid #ccd0d4; padding: 22px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-card-title { font-size: 16px; margin: 0; color: #1d2327; font-weight: 600; }
.km-divider { border: 0; border-top: 1px solid #f0f0f1; margin: 24px 0; }

/* Inputs e Addons */
.km-field-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #2c3338; }
.km-field-group small { display: block; color: #646970; font-size: 12px; margin-top: 4px; line-height: 1.3; }
.km-input { padding: 8px 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
.km-input:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.widefat { width: 100%; }

.km-input-addon-group { display: flex; align-items: center; }
.km-input-addon-group .km-addon { background: #f0f0f1; border: 1px solid #8c8f94; padding: 8px 12px; font-weight: 600; color: #50575e; font-size: 13px; }
.km-input-addon-group input { flex: 1; min-width: 0; }
.km-input-addon-group.km-addon-right input { border-right: none; border-radius: 4px 0 0 4px; }
.km-input-addon-group.km-addon-right .km-addon { border-radius: 0 4px 4px 0; }

/* Slider e Markup Info */
.km-slider-wrap { margin-top: 10px; }
.km-slider { width: 100%; height: 6px; border-radius: 3px; background: #dcdcde; outline: none; cursor: pointer; }
.km-markup-info-box { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; padding: 14px 16px; margin-top: 20px; }

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

/* Tabela Multicanal */
.km-channels-table-wrap { border: 1px solid #ccd0d4; border-radius: 6px; overflow: hidden; background: #fff; }
.km-channels-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
.km-channels-table th { background: #f6f7f7; padding: 12px 14px; font-weight: 600; color: #2c3338; border-bottom: 1px solid #ccd0d4; }
.km-channels-table td { padding: 12px 14px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }

/* Estados de Linha / Cores de Margem */
.km-row-danger  { background: #fdf2f2; color: #8a1f11; }
.km-row-warning { background: #fffdf0; color: #735100; }
.km-row-success { background: #f2f9f3; color: #0a5920; }
.km-row-premium { background: #f0f6fc; color: #0e4c7a; }

.km-pill { display: inline-block; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11.5px; }
.km-pill-danger  { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.km-pill-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
.km-pill-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.km-pill-premium { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }

/* Card Hero */
.km-hero-card { padding: 16px 20px; border-radius: 8px; font-size: 13px; border: 1px solid transparent; }
.km-hero-tag { font-weight: 700; text-transform: uppercase; font-size: 12px; }
.km-status-danger  { background: #fdf2f2; border-color: #f5c6cb; color: #721c24; }
.km-status-warning { background: #fffdf0; border-color: #ffeeba; color: #856404; }
.km-status-success { background: #f2f9f3; border-color: #c3e6cb; color: #155724; }

@media (max-width: 960px) {
    .km-split-grid { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const recipeSwitcher = document.getElementById('km-recipe-switcher');
    const costInput      = document.getElementById('km-direct-cost');
    const marginInput    = document.getElementById('km-target-margin');
    const marketInput    = document.getElementById('km-market-price');
    const priceSlider    = document.getElementById('km-price-slider');

    const markupMultText = document.getElementById('km-markup-multiplier');
    const markupTimesText= document.getElementById('km-markup-times');
    const simPriceLabel  = document.getElementById('km-label-sim-price');
    const channelsTbody  = document.getElementById('km-channels-tbody');
    const heroStatusBox  = document.getElementById('km-hero-status-box');
    const heroStatusTitle= document.getElementById('km-hero-status-title');
    const heroStatusDesc = document.getElementById('km-hero-status-desc');

    const channels = window.KM_SALES_CHANNELS || [];

    // Proteção de "Enter" acidental no formulário
    document.querySelectorAll('.km-input').forEach(input => {
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') event.preventDefault();
        });
    });

    function updateCalculations() {
        const cost = parseFloat(costInput.value) || 0;
        const targetMargin = parseFloat(marginInput.value) || 0;
        let simPrice = parseFloat(marketInput.value) || 0;

        // Se o preço simulado for zero (primeiro load), inicializa baseado no canal principal (índice 0)
        if (simPrice <= 0 && cost > 0) {
            const firstTax = channels.length > 0 ? channels[0].tax_percent : 0;
            const divisor = 1 - ((firstTax + targetMargin) / 100);
            simPrice = divisor > 0 ? (cost / divisor) : (cost * 1.5);
            marketInput.value = simPrice.toFixed(2);
            priceSlider.value = simPrice.toFixed(2);
        }

        // Ajusta limites dinâmicos do slider baseados no custo
        const maxSlider = Math.max(simPrice * 2.5, cost * 4, 15);
        priceSlider.max = maxSlider.toFixed(2);
        priceSlider.min = (cost > 0 ? cost * 0.8 : 0.1).toFixed(2);

        simPriceLabel.textContent = 'R$ ' + simPrice.toFixed(2).replace('.', ',');

        // Multiplicador de Markup Aparente
        const markup = cost > 0 ? (simPrice / cost) : 0;
        markupMultText.textContent = markup.toFixed(2).replace('.', ',') + 'x';
        markupTimesText.textContent = markup.toFixed(1).replace('.', ',') + 'x';

        renderChannelsTable(cost, targetMargin, simPrice);
    }

    function renderChannelsTable(cost, targetMargin, simPrice) {
        channelsTbody.innerHTML = '';
        let lowestMargin = 999;
        let criticalChannel = '';

        channels.forEach(ch => {
            const taxPct = parseFloat(ch.tax_percent) || 0;
            
            // 1. Preço Ideal: O valor perfeito a cobrar NESTE canal para sobrar a margem exata
            const divisor = 1 - ((taxPct + targetMargin) / 100);
            const idealPrice = divisor > 0 && cost > 0 ? (cost / divisor) : 0;

            // 2. Realidade Simulada: O que acontece se aplicarmos o Preço Global neste canal?
            const taxesRetained = simPrice * (taxPct / 100);
            const netProfit = simPrice - cost - taxesRetained;
            const realMargin = simPrice > 0 ? (netProfit / simPrice) * 100 : 0;

            if (realMargin < lowestMargin) {
                lowestMargin = realMargin;
                criticalChannel = ch.name;
            }

            // Definição das Cores de Estado
            let rowClass = 'km-row-success';
            let pillClass = 'km-pill-success';
            let statusText = 'Meta OK';

            if (netProfit <= 0 || realMargin < 5) {
                rowClass = 'km-row-danger';
                pillClass = 'km-pill-danger';
                statusText = 'Risco / Prejuízo';
            } else if (realMargin < targetMargin) {
                rowClass = 'km-row-warning';
                pillClass = 'km-pill-warning';
                statusText = 'Abaixo da Meta';
            } else if (realMargin > targetMargin + 15) {
                rowClass = 'km-row-premium';
                pillClass = 'km-pill-premium';
                statusText = 'Margem Alta';
            }

            const tr = document.createElement('tr');
            tr.className = rowClass;
            tr.innerHTML = `
                <td><strong>${ch.name}</strong></td>
                <td>${taxPct.toFixed(1).replace('.', ',')}%</td>
                <td><strong>R$ ${idealPrice.toFixed(2).replace('.', ',')}</strong></td>
                <td>R$ ${netProfit.toFixed(2).replace('.', ',')}</td>
                <td>
                    <span class="km-pill ${pillClass}">
                        ${realMargin.toFixed(1).replace('.', ',')}% (${statusText})
                    </span>
                </td>
            `;
            channelsTbody.appendChild(tr);
        });

        // Atualização do Hero Card de Resumo
        if (lowestMargin < 5) {
            heroStatusBox.className = 'km-hero-card km-status-danger';
            heroStatusTitle.textContent = '🚨 Alerta Crítico: Prejuízo ou Risco em Plataformas';
            heroStatusDesc.innerHTML = `Cobrando <strong>R$ ${simPrice.toFixed(2).replace('.', ',')}</strong>, você perde dinheiro ou empata em canais como <strong>${criticalChannel}</strong>. Considere ter um cardápio com preços maiores exclusivos para o delivery.`;
        } else if (lowestMargin < targetMargin) {
            heroStatusBox.className = 'km-hero-card km-status-warning';
            heroStatusTitle.textContent = '⚠️ Margem Desbalanceada Entre Canais';
            heroStatusDesc.innerHTML = `O preço atende a meta no balcão, mas fica abaixo da margem alvo em canais com taxas altas (ex: ${criticalChannel}).`;
        } else {
            heroStatusBox.className = 'km-hero-card km-status-success';
            heroStatusTitle.textContent = '✅ Precificação Blindada e Sustentável';
            heroStatusDesc.innerHTML = `Excelente! O preço escolhido é forte o suficiente para absorver até mesmo a taxa do seu canal mais caro e ainda deixar a margem limpa no seu bolso.`;
        }
    }

    // Bind de Eventos
    recipeSwitcher.addEventListener('change', function() {
        const selectedCost = parseFloat(this.value) || 0;
        costInput.value = selectedCost.toFixed(2);
        marketInput.value = '0.00'; // Força o recálculo do ideal com base no novo custo
        updateCalculations();
    });

    costInput.addEventListener('input', updateCalculations);
    marginInput.addEventListener('input', updateCalculations);

    marketInput.addEventListener('input', function() {
        const val = parseFloat(this.value) || 0;
        priceSlider.value = val;
        updateCalculations();
    });

    priceSlider.addEventListener('input', function() {
        const val = parseFloat(this.value) || 0;
        marketInput.value = val.toFixed(2