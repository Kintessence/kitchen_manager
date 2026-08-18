<?php
if (!defined('ABSPATH')) exit;

use KitchenManager\Modules\Ingredients\Services\IngredientService;
use KitchenManager\Modules\Recipes\Services\RecipeService;
use KitchenManager\Modules\BusinessProfile\Services\BusinessProfileService;

$ingredientService = new IngredientService();
$recipeService     = new RecipeService();
$profileService    = new BusinessProfileService();

$ingredients = $ingredientService->getIngredients();
$recipes     = $recipeService->getRecipes();
$profile     = $profileService->getProfile();
//$totals      = $profileService->calculateTotals($profile);
$totals = $profileService->calculateMetrics();
$totalIngredients = count($ingredients);
$totalRecipes     = count($recipes);
?>

<div class="wrap km-dashboard-wrap">
    
    <!-- BANNER DE BOAS-VINDAS -->
    <div class="km-welcome-banner">
        <div>
            <h1>🍳 Minha Cozinha — Painel de Controle</h1>
            <p>Visão geral da sua operação gastronômica, custos estruturais e inteligência de precificação.</p>
        </div>
        <div class="km-quick-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-ingredients')); ?>" class="button button-secondary">📦 Insumos</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-recipes')); ?>" class="button button-secondary">📋 Nova Ficha</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-pricing')); ?>" class="button button-primary">🏷️ Precificador</a>
        </div>
    </div>

    <!-- CARDS DE MÉTRICAS (KPIS PRINCIPAIS) -->
    <div class="km-kpi-grid">
        <div class="km-kpi-card">
            <div class="km-kpi-icon">📦</div>
            <div class="km-kpi-content">
                <span class="km-kpi-label">Insumos Cadastrados</span>
                <strong class="km-kpi-number"><?php echo $totalIngredients; ?></strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-ingredients')); ?>">Gerenciar insumos ➔</a>
            </div>
        </div>

        <div class="km-kpi-card">
            <div class="km-kpi-icon">📋</div>
            <div class="km-kpi-content">
                <span class="km-kpi-label">Fichas Técnicas Ativas</span>
                <strong class="km-kpi-number"><?php echo $totalRecipes; ?></strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-recipes')); ?>">Ver receitas ➔</a>
            </div>
        </div>

        <div class="km-kpi-card km-kpi-blue">
            <div class="km-kpi-icon">⏱️</div>
            <div class="km-kpi-content">
                <span class="km-kpi-label">Custo da Hora de Cozinha</span>
                <strong class="km-kpi-number">R$ <?php echo number_format($totals['cost_per_hour'], 2, ',', '.'); ?></strong>
                <span>R$ <?php echo number_format($totals['cost_per_minute'], 2, ',', '.'); ?>/minuto</span>
            </div>
        </div>

        <div class="km-kpi-card km-kpi-green">
            <div class="km-kpi-icon">🎯</div>
            <div class="km-kpi-content">
                <span class="km-kpi-label">Meta de Margem Líquida</span>
                <strong class="km-kpi-number"><?php echo number_format($profile['target_net_margin'], 1, ',', '.'); ?>%</strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-business-profile')); ?>">Ajustar perfil ➔</a>
            </div>
        </div>
    </div>

    <!-- SEÇÃO CENTRAL: GUIA VISUAL DE MARKUP & MARGEM -->
    <div class="km-guide-grid">
        
        <!-- CARD 1: O QUE É MARKUP E COMO CALCULAR -->
        <div class="km-guide-card">
            <div class="km-guide-header">
                <h2>📐 O que é Markup e como calcular?</h2>
                <span class="km-badge-tag">Olha de Baixo p/ Cima</span>
            </div>
            <p>O <strong>Markup</strong> é um índice multiplicador aplicado sobre o <strong>Custo Direto</strong> para encontrar o Preço de Venda necessário para cobrir despesas e gerar lucro.</p>
            
            <div class="km-formula-box">
                <strong>Fórmula do Markup Divisor:</strong><br>
                <code>Preço = Custo Direto ÷ [ 1 - (Taxas% + Margem%) ]</code>
            </div>

            <h4>Exemplo Prático:</h4>
            <ul>
                <li>Custo do Brigadeiro: <strong>R$ 1,00</strong></li>
                <li>Taxas (Cartão/iFood/Imposto): <strong>20% (0,20)</strong></li>
                <li>Margem Líquida Desejada: <strong>30% (0,30)</strong></li>
                <li><strong>Cálculo:</strong> <code>1,00 ÷ (1 - 0,50) = 1,00 ÷ 0,50 = R$ 2,00</code></li>
                <li><strong>Markup Multiplicador equivalente:</strong> <code>2,00x</code></li>
            </ul>
        </div>

        <!-- CARD 2: O QUE É MARGEM E POR QUE NUNCA É 100% -->
        <div class="km-guide-card">
            <div class="km-guide-header">
                <h2>💰 O que é Margem e o Mito dos 100%?</h2>
                <span class="km-badge-tag km-badge-orange">Olha de Cima p/ Baixo</span>
            </div>
            <p>A <strong>Margem de Lucro Líquida</strong> representa a porcentagem de cada real que sobra limpo no caixa da empresa <em>depois</em> que todos os custos, insumos e taxas foram pagos.</p>
            
            <div class="km-formula-box">
                <strong>Fórmula da Margem Líquida:</strong><br>
                <code>Margem% = (Lucro Líquido em R$ ÷ Preço de Venda) × 100</code>
            </div>

            <h4>O Mito dos 100%:</h4>
            <p>O preço de venda é o todo ($100\%$). Se você gastou $R\$\ 1,00$ e vendeu por $R\$\ 2,00$, seu lucro foi de $R\$\ 1,00$, ou seja, <strong>sua margem é de exatamente 50%</strong>. Ter $100\%$ de lucro só existiria se o seu custo fosse $R\$\ 0,00$.</p>
        </div>

    </div>

    <!-- SEÇÃO DE RECOMENDAÇÕES RÁPIDAS -->
    <div class="km-tips-banner">
        <h3>💡 Boas Práticas para o seu Cardápio:</h3>
        <div class="km-tips-grid">
            <div class="km-tip-item">
                <strong>1. Não use o mesmo Markup para tudo</strong>
                <p>Itens de alto giro (café, docinho) aceitam margens menores (20%). Itens artesanais e encomendas complexas pedem 40% a 60%.</p>
            </div>
            <div class="km-tip-item">
                <strong>2. Embalagem e fita são custos diretos</strong>
                <p>Se a embalagem bonita custa R$ 3,00, ela precisa estar cadastrada como insumo da receita para não corroer sua sobra.</p>
            </div>
            <div class="km-tip-item">
                <strong>3. Revise preços a cada 30 dias</strong>
                <p>A inflação de manteiga, chocolate e leite condensado corrói margens silenciosamente se o custo de compra não for atualizado.</p>
            </div>
        </div>
    </div>

</div>

<style>
.km-dashboard-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.km-welcome-banner { background: #fff; border: 1px solid #ccd0d4; padding: 20px 24px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px; }
.km-welcome-banner h1 { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; }
.km-welcome-banner p { margin: 0; color: #646970; font-size: 13px; }
.km-quick-actions { display: flex; gap: 8px; }

/* Grid de KPIs */
.km-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.km-kpi-card { background: #fff; border: 1px solid #ccd0d4; padding: 18px 20px; border-radius: 8px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-kpi-icon { font-size: 28px; background: #f0f0f1; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
.km-kpi-content { display: flex; flex-direction: column; }
.km-kpi-label { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #646970; }
.km-kpi-number { font-size: 20px; color: #1d2327; margin: 2px 0; }
.km-kpi-card a { font-size: 12px; text-decoration: none; font-weight: 600; color: #2271b1; }
.km-kpi-card small, .km-kpi-card span { font-size: 12px; color: #646970; }

.km-kpi-blue { border-left: 4px solid #2271b1; }
.km-kpi-green { border-left: 4px solid #007017; }

/* Guias de Markup e Margem */
.km-guide-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.km-guide-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-guide-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px; }
.km-guide-header h2 { margin: 0; font-size: 16px; color: #1d2327; }
.km-badge-tag { background: #f0f6fc; color: #2271b1; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.km-badge-orange { background: #fcf3e8; color: #b26200; }
.km-guide-card p { font-size: 13px; line-height: 1.5; color: #2c3338; margin: 0 0 12px 0; }
.km-formula-box { background: #f6f7f7; border-left: 3px solid #8c8f94; padding: 10px 14px; border-radius: 0 4px 4px 0; margin-bottom: 14px; font-size: 12.5px; }
.km-formula-box code { background: #fff; padding: 2px 6px; border-radius: 3px; border: 1px solid #dcdcde; font-weight: bold; }
.km-guide-card h4 { margin: 12px 0 6px 0; font-size: 13px; color: #1d2327; }
.km-guide-card ul { margin: 0 0 0 18px; padding: 0; font-size: 12.5px; color: #3c434a; line-height: 1.6; }

/* Banner de Dicas */
.km-tips-banner { background: #fdfaf2; border: 1px solid #e0d0b0; border-radius: 8px; padding: 18px 22px; }
.km-tips-banner h3 { margin: 0 0 12px 0; font-size: 15px; color: #6e4e00; }
.km-tips-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.km-tip-item strong { display: block; font-size: 13px; color: #1d2327; margin-bottom: 4px; }
.km-tip-item p { margin: 0; font-size: 12px; line-height: 1.4; color: #50575e; }
</style>