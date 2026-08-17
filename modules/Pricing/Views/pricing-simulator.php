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
$defaultCost   = isset($_GET['cost']) && (float) $_GET['cost'] > 0 ? (float) $_GET['cost'] : 0.85;
$defaultMargin = (float) ($profile['target_net_margin'] ?? 30.0);
$salesChannels = $profile['sales_channels'] ?? [
    ['name' => 'Balcão / Pix', 'tax_percent' => 0.0],
    ['name' => 'Cartão de Débito', 'tax_percent' => 1.8],
    ['name' => 'Cartão de Crédito (1x)', 'tax_percent' => 3.8],
    ['name' => 'iFood (Entrega Própria)', 'tax_percent' => 12.0],
    ['name' => 'iFood (Entrega Parceira)', 'tax_percent' => 27.0]
];
?>

<div class="wrap km-pricing-wrap">
    
    <div class="km-header-title">
        <h1>🧭 Formação de Preço e Diagnóstico Financeiro</h1>
        <p class="description">
            Identifique custos invisíveis, aplique o 
            <span class="km-tooltip-trigger" data-tooltip="Fórmula matemática que calcula o preço de venda de cima para baixo, garantindo que as taxas do cartão/app incidam sobre o valor final sem corroer o custo ou o lucro.">Markup Divisor ℹ️</span> 
            e veja a viabilidade em múltiplos canais de venda.
        </p>
    </div>

    <!-- SELETOR OU IDENTIFICADOR DA RECEITA ATIVA -->
    <div class="km-recipe-badge-bar">
        <div class="km-recipe-badge-info">
            <span class="km-tag-label">📋 Ficha Técnica em Análise:</span>
            <select id="km-recipe-switcher" class="km-select-recipe">
                <option value="<?php echo esc_attr($defaultCost); ?>" <?php echo empty($recipeName) ? 'selected' : ''; ?>>
                    <?php echo !empty($recipeName) ? esc_html($recipeName) . ' (R$ ' . number_format($defaultCost, 2, ',', '.') . ')' : '— Simulação Manual Avulsa —'; ?>
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
        <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-recipes')); ?>" class="button button-secondary button-small">
            📋 Gerenciar Fichas
        </a>
    </div>

    <div class="km-pricing-grid">
        
        <!-- PAINEL ESQUERDO: CONTROLES & SIMULAÇÃO -->
        <div class="km-card km-controls-col">
            
            <h2 class="km-block-title">1. Parâmetros de Custo e Meta</h2>

            <div class="km-field-group">
                <label for="km-direct-cost">
                    Custo Direto Unitário (Insumos + Embalagem) [R$]:
                    <span class="km-tooltip-trigger" data-tooltip="A soma de todos os ingredientes fracionados mais a embalagem individual do produto.">ℹ️</span>
                </label>
                <input type="number" id="km-direct-cost" step="0.01" min="0" value="<?php echo esc_attr(number_format($defaultCost, 2, '.', '')); ?>" class="km-input-main">
                <small>Quanto custa produzir exatamente 1 porção ou unidade.</small>
            </div>

            <div class="km-field-group">
                <label for="km-target-margin">
                    Meta de Margem Líquida Desejada (%):
                    <span class="km-tooltip-trigger" data-tooltip="A porcentagem do preço de venda que fica limpa na conta bancária da empresa após pagar todos os custos e taxas.">ℹ️</span>
                </label>
                <input type="number" id="km-target-margin" step="0.1" min="1" max="90" value="<?php echo esc_attr($defaultMargin); ?>" class="km-input-main">
                <small>Definida globalmente no seu Perfil do Negócio (ajustável aqui).</small>
            </div>

            <hr class="km-divider">

            <h2 class="km-block-title">2. Simulação do Preço Praticado no Mercado</h2>

            <div class="km-field-group">
                <label for="km-market-price">
                    Preço que você planeja cobrar [R$]:
                    <span class="km-tooltip-trigger" data-tooltip="O valor de etiqueta na sua loja física ou delivery. Ao alterar este valor, todos os canais ao lado recalculam sua viabilidade.">ℹ️</span>
                </label>
                <input type="number" id="km-market-price" step="0.01" min="0" value="0.00" class="km-input-main" style="font-weight: bold; font-size: 18px; color: #2271b1;">
                <div class="km-slider-wrap">
                    <input type="range" id="km-price-slider" min="0.1" max="50" step="0.05" value="1.00" class="km-slider">
                </div>
                <small>Arraste o slider para testar preços e diagnosticar a saúde da margem em cada canal.</small>
            </div>

            <!-- CAIXA DE MARKUP EXPLICADO -->
            <div class="km-markup-info-box">
                <strong>📐 Multiplicador de Markup Aplicado:</strong>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                    <span id="km-markup-multiplier" style="font-size: 20px; font-weight: 700; color: #1d2327;">0.00x</span>
                    <small style="max-width: 220px; text-align: right; color: #50575e;">
                        Preço é <strong id="km-markup-times">0x</strong> o custo de produção.
                    </small>
                </div>
            </div>

        </div>

        <!-- PAINEL DIREITO: MULTICANAL EXPLODIDO EM TEMPO REAL -->
        <div class="km-card km-results-col">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 class="km-block-title" style="margin: 0;">💡 Diagnóstico Multicanal em Tempo Real</h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-business-profile')); ?>" style="font-size: 11px; text-decoration: none; font-weight: 600;">⚙️ Editar Taxas</a>
            </div>
            <p style="font-size: 12.5px; color: #646970; margin: 0 0 16px 0;">
                Veja como o mesmo preço de <strong id="km-label-sim-price">R$ 0,00</strong> se comporta em cada canal cadastrado:
            </p>

            <!-- TABELA MULTICANAL COM CORES DINÂMICAS -->
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
                        <!-- Gerado via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- RESUMO DO CANAL PRINCIPAL (CARD DE DESTAQUE) -->
            <div id="km-hero-status-box" class="km-hero-card km-status-success" style="margin-top: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="km-hero-tag" id="km-hero-status-title">✅ Margem Saudável</span>
                    <span style="font-size: 12px; opacity: 0.9;">Canal Balcão/Pix</span>
                </div>
                <p id="km-hero-status-desc" style="margin: 8px 0 0 0; font-size: 13px; line-height: 1.4;">
                    No balcão você retém a margem máxima projetada.
                </p>
            </div>

        </div>

    </div>
</div>

<!-- DADOS DOS CANAIS EMBUTIDOS PARA O JAVASCRIPT -->
<script>
window.KM_SALES_CHANNELS = <?php echo json_encode($salesChannels); ?>;
</script>

<style>
.km-pricing-wrap { max-width: 1240px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.km-header-title h1 { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; }
.km-header-title p { margin: 0; color: #646970; font-size: 13px; }

/* Barra de Seleção da Receita */
.km-recipe-badge-bar { background: #ffffff; border: 1px solid #ccd0d4; padding: 12px 18px; border-radius: 8px; margin: 16px 0 20px 0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-recipe-badge-info { display: flex; align-items: center; gap: 10px; }
.km-tag-label { font-weight: 700; font-size: 13px; color: #1d2327; }
.km-select-recipe { font-weight: 600; font-size: 13px; padding: 5px 10px; border-radius: 4px; border: 1px solid #8c8f94; min-width: 320px; }

/* Grid Principal */
.km-pricing-grid { display: grid; grid-template-columns: 420px 1fr; gap: 22px; }
.km-card { background: #ffffff; border: 1px solid #ccd0d4; padding: 22px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-block-title { font-size: 15px; margin: 0 0 16px 0; color: #1d2327; font-weight: 700; }
.km-divider { border: 0; border-top: 1px solid #f0f0f1; margin: 20px 0; }

.km-field-group { margin-bottom: 16px; }
.km-field-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #2c3338; }
.km-field-group small { display: block; color: #646970; font-size: 12px; margin-top: 4px; }
.km-input-main { width: 100%; padding: 8px 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; box-sizing: border-box; }

.km-slider-wrap { margin-top: 8px; }
.km-slider { width: 100%; height: 6px; border-radius: 3px; background: #dcdcde; outline: none; cursor: pointer; }

.km-markup-info-box { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; padding: 12px 14px; margin-top: 18px; }

/* Tooltips */
.km-tooltip-trigger { display: inline-block; cursor: help; color: #2271b1; font-weight: bold; position: relative; }
.km-tooltip-trigger:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: #1d2327;
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 11.5px;
    line-height: 1.4;
    width: 240px;
    z-index: 999;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    font-weight: normal;
    text-align: left;
}
.km-tooltip-trigger:hover::before {
    content: "";
    position: absolute;
    bottom: 105%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 5px;
    border-style: solid;
    border-color: #1d2327 transparent transparent transparent;
    z-index: 999;
}

/* Tabela Multicanal */
.km-channels-table-wrap { border: 1px solid #ccd0d4; border-radius: 6px; overflow: hidden; }
.km-channels-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12.5px; }
.km-channels-table th { background: #f0f0f1; padding: 9px 12px; font-weight: 700; color: #2c3338; border-bottom: 1px solid #ccd0d4; }
.km-channels-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f1; }

/* Estados de Linha / Cores de Margem */
.km-row-danger  { background: #fdf2f2; color: #8a1f11; }
.km-row-warning { background: #fffdf0; color: #735100; }
.km-row-success { background: #f2f9f3; color: #0a5920; }
.km-row-premium { background: #f0f6fc; color: #0e4c7a; }

.km-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-weight: 700; font-size: 11px; }
.km-pill-danger  { background: #f8d7da; color: #721c24; }
.km-pill-warning { background: #fff3cd; color: #856404; }
.km-pill-success { background: #d4edda; color: #155724; }
.km-pill-premium { background: #cce5ff; color: #004085; }

/* Card Hero */
.km-hero-card { padding: 14px 18px; border-radius: 6px; font-size: 13px; border: 1px solid transparent; }
.km-hero-tag { font-weight: 700; text-transform: uppercase; font-size: 12px; }
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

    function updateCalculations() {
        const cost = parseFloat(costInput.value) || 0;
        const targetMargin = parseFloat(marginInput.value) || 0;
        let simPrice = parseFloat(marketInput.value) || 0;

        // Se o preço simulado for zero, inicializa com base no canal 0
        if (simPrice <= 0 && cost > 0) {
            const firstTax = channels.length > 0 ? channels[0].tax_percent : 0;
            const divisor = 1 - ((firstTax + targetMargin) / 100);
            simPrice = divisor > 0 ? (cost / divisor) : (cost * 1.5);
            marketInput.value = simPrice.toFixed(2);
            priceSlider.value = simPrice.toFixed(2);
        }

        // Ajusta limites do slider
        const maxSlider = Math.max(simPrice * 2.5, cost * 4, 15);
        priceSlider.max = maxSlider.toFixed(2);
        priceSlider.min = (cost > 0 ? cost * 0.8 : 0.1).toFixed(2);

        simPriceLabel.textContent = 'R$ ' + simPrice.toFixed(2).replace('.', ',');

        // Markup Multiplicador
        const markup = cost > 0 ? (simPrice / cost) : 0;
        markupMultText.textContent = markup.toFixed(2).replace('.', ',') + 'x';
        markupTimesText.textContent = markup.toFixed(1).replace('.', ',') + 'x';

        // Renderiza as linhas dos canais
        renderChannelsTable(cost, targetMargin, simPrice);
    }

    function renderChannelsTable(cost, targetMargin, simPrice) {
        channelsTbody.innerHTML = '';
        let lowestMargin = 999;
        let criticalChannel = '';

        channels.forEach(ch => {
            const taxPct = parseFloat(ch.tax_percent) || 0;
            
            // 1. Preço Ideal para atingir a meta no canal
            const divisor = 1 - ((taxPct + targetMargin) / 100);
            const idealPrice = divisor > 0 && cost > 0 ? (cost / divisor) : 0;

            // 2. Diagnóstico cobrando o Preço Simulado
            const taxesRetained = simPrice * (taxPct / 100);
            const netProfit = simPrice - cost - taxesRetained;
            const realMargin = simPrice > 0 ? (netProfit / simPrice) * 100 : 0;

            if (realMargin < lowestMargin) {
                lowestMargin = realMargin;
                criticalChannel = ch.name;
            }

            // Definição de Classe e Badge
            let rowClass = 'km-row-success';
            let pillClass = 'km-pill-success';
            let statusText = 'Meta OK';

            if (netProfit <= 0 || realMargin < 5) {
                rowClass = 'km-row-danger';
                pillClass = 'km-pill-danger';
                statusText = 'Prejuízo / Crítico';
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

        // Atualiza Hero Card de Diagnóstico Geral
        if (lowestMargin < 5) {
            heroStatusBox.className = 'km-hero-card km-status-danger';
            heroStatusTitle.textContent = '🚨 Alerta de Prejuízo em Canais de Alta Taxa';
            heroStatusDesc.innerHTML = `Cobrando <strong>R$ ${simPrice.toFixed(2).replace('.', ',')}</strong>, você perde dinheiro ou empata em canais como <strong>${criticalChannel}</strong>. Considere aplicar preços diferenciados para o delivery.`;
        } else if (lowestMargin < targetMargin) {
            heroStatusBox.className = 'km-hero-card km-status-warning';
            heroStatusTitle.textContent = '⚠️ Margem Desbalanceada Entre Canais';
            heroStatusDesc.innerHTML = `Este preço atende bem no balcão, mas fica abaixo da meta em plataformas de entrega.`;
        } else {
            heroStatusBox.className = 'km-hero-card km-status-success';
            heroStatusTitle.textContent = '✅ Preço Sustentável em Todos os Canais';
            heroStatusDesc.innerHTML = `Parabéns! Mesmo no canal mais pesado com taxas altas, sua margem líquida permanece acima da meta.`;
        }
    }

    // Eventos
    recipeSwitcher.addEventListener('change', function() {
        const selectedCost = parseFloat(this.value) || 0;
        costInput.value = selectedCost.toFixed(2);
        marketInput.value = '0.00'; // Força recálculo do ideal
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
        marketInput.value = val.toFixed(2);
        updateCalculations();
    });

    updateCalculations();
});
</script>