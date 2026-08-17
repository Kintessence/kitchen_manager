<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap km-profile-wrap">
    
    <!-- BARRA SUPERIOR -->
    <div class="km-header-bar">
        <div>
            <h1 style="margin: 0; font-size: 22px; color: #1d2327;">🏢 Diagnóstico & Estrutura do Negócio</h1>
            <p style="margin: 4px 0 0 0; color: #646970;">Mapeie salários, despesas operacionais e taxas de canais de venda para alimentar a formação automática de preços.</p>
        </div>
        <div>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_reset_business_profile'), 'km_reset_profile_action')); ?>" 
               class="button button-secondary"
               onclick="return confirm('Deseja redefinir para os valores médios de referência de mercado?');">
                🔄 Restaurar Padrões de Mercado
            </a>
        </div>
    </div>

    <?php if ($status === 'saved'): ?>
        <div class="notice notice-success is-dismissible" style="margin-top: 15px;">
            <p><strong>✅ Estrutura do negócio atualizada com sucesso! O custo da hora produtiva e taxas foram recalculados.</strong></p>
        </div>
    <?php elseif ($status === 'reset'): ?>
        <div class="notice notice-info is-dismissible" style="margin-top: 15px;">
            <p><strong>✨ Padrões de mercado carregados com sucesso! Ajuste os valores de acordo com a sua realidade.</strong></p>
        </div>
    <?php endif; ?>

    <!-- CARDS DE DIAGNÓSTICO EM TEMPO REAL (TOPO) -->
    <div class="km-kpi-grid">
        <div class="km-kpi-card">
            <span class="km-kpi-title">Mão de Obra Total</span>
            <strong class="km-kpi-val" id="km-kpi-labor">R$ <?php echo number_format($totals['total_labor'], 2, ',', '.'); ?></strong>
            <small>Pró-labore + Colaboradores</small>
        </div>
        <div class="km-kpi-card">
            <span class="km-kpi-title">Despesas Fixas & Variáveis</span>
            <strong class="km-kpi-val" id="km-kpi-expenses">R$ <?php echo number_format($totals['total_fixed'] + $totals['total_variable'], 2, ',', '.'); ?></strong>
            <small>Estrutura + Insumos de apoio</small>
        </div>
        <div class="km-kpi-card km-kpi-highlight">
            <span class="km-kpi-title">Custo da Sua Hora de Cozinha</span>
            <strong class="km-kpi-val" id="km-kpi-hour" style="color: #2271b1;">R$ <?php echo number_format($totals['cost_per_hour'], 2, ',', '.'); ?> / hora</strong>
            <small id="km-kpi-minute">(R$ <?php echo number_format($totals['cost_per_minute'], 2, ',', '.'); ?> por minuto de forno/bancada)</small>
        </div>
    </div>

    <!-- FORMULÁRIO DE GESTÃO DO NEGÓCIO -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-profile-form">
        <input type="hidden" name="action" value="km_save_business_profile">
        <?php wp_nonce_field('km_save_profile_action', 'km_profile_nonce'); ?>

        <div class="km-main-grid">
            
            <!-- COLUNA DA ESQUERDA: LISTAS DINÂMICAS & CAMPOS -->
            <div class="km-forms-col">
                
                <!-- SEÇÃO 1: PESSOAS & SALÁRIOS -->
                <div class="km-section-card">
                    <div class="km-section-header">
                        <h2>👥 1. Pessoas & Salários (Mão de Obra Real)</h2>
                        <button type="button" class="button button-small" id="km-add-labor-btn">➕ Adicionar Pessoa</button>
                    </div>
                    <p class="km-section-desc">Informe o quanto cada sócio precisa tirar para viver (pró-labore) e os salários de ajudantes ou diárias de equipe.</p>
                    
                    <div id="km-labor-list" class="km-repeater-container">
                        <?php foreach ($profile['labor_items'] as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="labor_items[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: Pró-labore Sócio" class="km-input-text" required>
                                <div class="km-input-currency">
                                    <span>R$</span>
                                    <input type="number" name="labor_items[<?php echo $idx; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" step="0.1" min="0" class="km-input-num km-calc-labor" required>
                                </div>
                                <button type="button" class="km-remove-row-btn" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SEÇÃO 2: CUSTOS FIXOS MENSAIS -->
                <div class="km-section-card">
                    <div class="km-section-header">
                        <h2>🏠 2. Contas Fixas & Estrutura Mensal</h2>
                        <button type="button" class="button button-small" id="km-add-fixed-btn">➕ Adicionar Conta</button>
                    </div>
                    <p class="km-section-desc">Despesas que chegam todo mês mesmo que você não venda nada (energia, gás, água, internet, contabilidade).</p>
                    
                    <div id="km-fixed-list" class="km-repeater-container">
                        <?php foreach ($profile['fixed_expenses'] as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="fixed_expenses[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: Gás de Cozinha" class="km-input-text" required>
                                <div class="km-input-currency">
                                    <span>R$</span>
                                    <input type="number" name="fixed_expenses[<?php echo $idx; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" step="0.1" min="0" class="km-input-num km-calc-fixed" required>
                                </div>
                                <button type="button" class="km-remove-row-btn" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SEÇÃO 3: CUSTOS VARIÁVEIS DE APOIO -->
                <div class="km-section-card">
                    <div class="km-section-header">
                        <h2>🛍️ 3. Despesas Variáveis / Embalagens Secundárias</h2>
                        <button type="button" class="button button-small" id="km-add-var-btn">➕ Adicionar Item</button>
                    </div>
                    <p class="km-section-desc">Itens que acompanham o volume de vendas (fitas, sacolas, lacres, motoboys avulsos).</p>
                    
                    <div id="km-var-list" class="km-repeater-container">
                        <?php foreach ($profile['variable_expenses'] as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="variable_expenses[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: Sacolas Kraft" class="km-input-text" required>
                                <div class="km-input-currency">
                                    <span>R$</span>
                                    <input type="number" name="variable_expenses[<?php echo $idx; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" step="0.1" min="0" class="km-input-num km-calc-var" required>
                                </div>
                                <button type="button" class="km-remove-row-btn" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SEÇÃO 4: CANAIS DE VENDA & TAXAS -->
                <div class="km-section-card">
                    <div class="km-section-header">
                        <h2>💳 4. Canais de Venda & Meios de Pagamento</h2>
                        <button type="button" class="button button-small" id="km-add-channel-btn">➕ Adicionar Canal</button>
                    </div>
                    <p class="km-section-desc">Taxas cobradas por maquininhas, cartões e plataformas de delivery sobre o valor bruto da venda.</p>
                    
                    <div id="km-channel-list" class="km-repeater-container">
                        <?php foreach ($profile['sales_channels'] as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="sales_channels[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: iFood Entrega Parceira" class="km-input-text" required>
                                <div class="km-input-percent">
                                    <input type="number" name="sales_channels[<?php echo $idx; ?>][tax_percent]" value="<?php echo esc_attr($item['tax_percent']); ?>" step="0.1" min="0" max="90" class="km-input-num" required>
                                    <span>%</span>
                                </div>
                                <button type="button" class="km-remove-row-btn" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SEÇÃO 5: CAPACIDADE PRODUTIVA & MARGEM PADRÃO -->
                <div class="km-section-card">
                    <h2>⏱️ 5. Ritmo de Produção & Margem Líquida Alvo</h2>
                    <div class="km-grid-2col" style="margin-top: 10px;">
                        <div class="km-field-box">
                            <label for="km-hours-input">Horas Produtivas da Cozinha (Mês):</label>
                            <input type="number" id="km-hours-input" name="monthly_productive_hours" value="<?php echo esc_attr($profile['monthly_productive_hours']); ?>" step="0.1" min="1" required>
                            <small>Ex: 8 horas/dia x 20 dias no mês = 160 horas.</small>
                        </div>
                        <div class="km-field-box">
                            <label for="km-margin-input">Margem Líquida Padrão da Empresa (%):</label>
                            <div class="km-input-percent">
                                <input type="number" id="km-margin-input" name="target_net_margin" value="<?php echo esc_attr($profile['target_net_margin']); ?>" step="0.1" min="0" max="90" required>
                                <span>%</span>
                            </div>
                            <small>O lucro limpo que deve sobrar no caixa após pagar tudo.</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="button button-primary button-large" style="width: 100%; height: 46px; font-size: 16px; margin-top: 10px;">
                    💾 Salvar Configurações do Negócio
                </button>

            </div>

            <!-- COLUNA DA DIREITA: CENTRAL EDUCATIVA & MITOS FINANCEIROS -->
            <div class="km-sidebar-col">
                
                <!-- CARD EDUCATIVO 1: O MITO DOS 100% DE LUCRO -->
                <div class="km-edu-card km-edu-warning">
                    <h3>🚫 Mito: "Posso ter 100% ou 200% de Lucro?"</h3>
                    <p><strong>A resposta matemática é NÃO.</strong> O preço de venda é o todo (100%). Se você gastou R$ 1,00 para produzir e vendeu por R$ 2,00:</p>
                    <ul>
                        <li>Seu custo levou <strong>50%</strong> do dinheiro recebido.</li>
                        <li>Sua margem de lucro bruta foi de <strong>50%</strong> (e não 100%).</li>
                    </ul>
                    <p style="margin-bottom: 0;">Ter 100% de lucro significaria que você não teve nenhum centavo de custo — nem insumos, nem gás, nem taxa de cartão.</p>
                </div>

                <!-- CARD EDUCATIVO 2: MARKUP VS MARGEM -->
                <div class="km-edu-card km-edu-info">
                    <h3>💡 Markup vs. Margem de Lucro</h3>
                    <p>Eles olham para a mesma conta, mas em direções opostas:</p>
                    <ul>
                        <li><strong>Markup (Multiplicador):</strong> Olha de <em>baixo para cima</em> (sobre o que você gastou). Ex: "Multiplico meu custo por 2.5x".</li>
                        <li><strong>Margem Líquida:</strong> Olha de <em>cima para baixo</em> (sobre o preço final que entra na sua conta).</li>
                    </ul>
                    <p style="margin-bottom: 0;">Um Markup de 100% (multiplicar custo por 2) equivale a apenas 50% de margem. Se suas taxas e impostos somarem 30%, sua margem real cai para 20%.</p>
                </div>

                <!-- CARD EDUCATIVO 3: A ARMADILHA DO IFOOD -->
                <div class="km-edu-card km-edu-danger">
                    <h3>🛵 A Armadilha de Somar Taxas no Final</h3>
                    <p>Se o iFood cobra <strong>27%</strong> de comissão e seu produto custa R$ 10,00:</p>
                    <ul>
                        <li><strong>O erro comum:</strong> Fazer <code>10 + 27% = R$ 12,70</code>.</li>
                        <li><strong>A mordida real:</strong> O iFood vai descontar 27% sobre os <strong>R$ 12,70 cheios</strong> (-R$ 3,43).</li>
                        <li><strong>O prejuízo:</strong> Sobram apenas <strong>R$ 9,27</strong> na sua mão (menos do que você gastou!).</li>
                    </ul>
                    <p style="margin-bottom: 0;">O cálculo correto exige o <strong>Markup Divisor</strong>, que o nosso sistema faz automaticamente para você.</p>
                </div>

                <!-- CARD EDUCATIVO 4: QUANDO TORNAR VARIÁVEL EM FIXO? -->
                <div class="km-edu-card km-edu-tip">
                    <h3>🔁 Dica: Quando tornar um gasto variável em Fixo?</h3>
                    <p>Se você gasta todo mês uma quantidade previsível de <strong>gás, fitas ou sacolas</strong>, é muito mais seguro somá-los nas <strong>Contas Fixas Mensais</strong>.</p>
                    <p style="margin-bottom: 0;">Dessa forma, o sistema rateia esse custo direto no valor da sua hora de trabalho, garantindo que você nunca esqueça de cobrar o gás em nenhuma receita!</p>
                </div>

            </div>

        </div>
    </form>
</div>

<style>
.km-profile-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.km-header-bar { background: #ffffff; padding: 16px 20px; border: 1px solid #ccd0d4; border-radius: 8px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }

/* Grid de KPIs */
.km-kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
.km-kpi-card { background: #fff; border: 1px solid #ccd0d4; padding: 16px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.km-kpi-card.km-kpi-highlight { border-left: 4px solid #2271b1; background: #f0f6fc; }
.km-kpi-title { display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #50575e; margin-bottom: 4px; }
.km-kpi-val { display: block; font-size: 22px; color: #1d2327; margin-bottom: 2px; }
.km-kpi-card small { color: #646970; font-size: 12px; }

/* Grid Principal */
.km-main-grid { display: grid; grid-template-columns: 1fr 420px; gap: 24px; }
.km-section-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px 22px; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-section-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f1; padding-bottom: 8px; margin-bottom: 6px; }
.km-section-header h2 { margin: 0; font-size: 16px; color: #1d2327; }
.km-section-desc { font-size: 13px; color: #646970; margin-top: 0; margin-bottom: 14px; }

/* Repeater e Linhas */
.km-repeater-container { display: flex; flex-direction: column; gap: 8px; }
.km-item-row { display: flex; align-items: center; gap: 8px; }
.km-item-row .km-input-text { flex: 1; padding: 6px 10px; border: 1px solid #8c8f94; border-radius: 4px; }
.km-input-currency, .km-input-percent { display: flex; align-items: center; width: 140px; }
.km-input-currency span, .km-input-percent span { background: #f0f0f1; border: 1px solid #8c8f94; padding: 6px 10px; font-weight: 600; color: #50575e; font-size: 13px; }
.km-input-currency span { border-right: 0; border-radius: 4px 0 0 4px; }
.km-input-percent span { border-left: 0; border-radius: 0 4px 4px 0; }
.km-input-currency input { border-radius: 0 4px 4px 0; width: 100%; padding: 6px; border: 1px solid #8c8f94; }
.km-input-percent input { border-radius: 4px 0 0 4px; width: 100%; padding: 6px; border: 1px solid #8c8f94; }
.km-remove-row-btn { background: none; border: none; color: #b32d2e; font-size: 16px; font-weight: bold; cursor: pointer; padding: 4px 8px; border-radius: 4px; line-height: 1; }
.km-remove-row-btn:hover { background: #f8d7da; }

/* Grid 2 colunas */
.km-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.km-field-box label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px; color: #1d2327; }
.km-field-box input { width: 100%; padding: 6px; border: 1px solid #8c8f94; border-radius: 4px; box-sizing: border-box; }
.km-field-box small { color: #646970; font-size: 12px; display: block; margin-top: 3px; }

/* Cards Educativos */
.km-sidebar-col { display: flex; flex-direction: column; gap: 16px; }
.km-edu-card { background: #fff; border: 1px solid #ccd0d4; padding: 16px 18px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-edu-card h3 { margin: 0 0 8px 0; font-size: 14px; }
.km-edu-card p { font-size: 12.5px; line-height: 1.5; color: #2c3338; margin: 0 0 8px 0; }
.km-edu-card ul { margin: 6px 0 8px 18px; padding: 0; font-size: 12px; color: #3c434a; }
.km-edu-card li { margin-bottom: 4px; }
.km-edu-card code { background: rgba(0,0,0,0.06); padding: 1px 4px; border-radius: 3px; font-size: 11px; }

.km-edu-warning { border-left: 4px solid #dba617; background: #fcf9e8; }
.km-edu-info { border-left: 4px solid #2271b1; background: #f0f6fc; }
.km-edu-danger { border-left: 4px solid #d63638; background: #fcf0f1; }
.km-edu-tip { border-left: 4px solid #007017; background: #f4fbf5; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kpiLabor = document.getElementById('km-kpi-labor');
    const kpiExpenses = document.getElementById('km-kpi-expenses');
    const kpiHour = document.getElementById('km-kpi-hour');
    const kpiMinute = document.getElementById('km-kpi-minute');
    const hoursInput = document.getElementById('km-hours-input');

    function recalculateKpis() {
        let totalLabor = 0;
        document.querySelectorAll('.km-calc-labor').forEach(el => totalLabor += parseFloat(el.value) || 0);

        let totalFixed = 0;
        document.querySelectorAll('.km-calc-fixed').forEach(el => totalFixed += parseFloat(el.value) || 0);

        let totalVar = 0;
        document.querySelectorAll('.km-calc-var').forEach(el => totalVar += parseFloat(el.value) || 0);

        const totalExpenses = totalFixed + totalVar;
        const totalStructure = totalLabor + totalExpenses;

        const hours = parseFloat(hoursInput.value) || 160;
        const costPerHour = hours > 0 ? (totalStructure / hours) : 0;
        const costPerMinute = costPerHour / 60;

        kpiLabor.textContent = 'R$ ' + totalLabor.toFixed(2).replace('.', ',');
        kpiExpenses.textContent = 'R$ ' + totalExpenses.toFixed(2).replace('.', ',');
        kpiHour.textContent = 'R$ ' + costPerHour.toFixed(2).replace('.', ',') + ' / hora';
        kpiMinute.textContent = '(R$ ' + costPerMinute.toFixed(2).replace('.', ',') + ' por minuto de forno/bancada)';
    }

    function bindRow(row) {
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', recalculateKpis);
        });
        const removeBtn = row.querySelector('.km-remove-row-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const parent = row.parentElement;
                if (parent.children.length > 1) {
                    row.remove();
                    recalculateKpis();
                } else {
                    alert('Mantenha ao menos um item nesta lista.');
                }
            });
        }
    }

    document.querySelectorAll('.km-item-row').forEach(bindRow);
    hoursInput.addEventListener('input', recalculateKpis);

    // Botões de Adicionar Novo Item
    function setupAddButton(btnId, containerId, prefix, isPercent) {
        const btn = document.getElementById(btnId);
        const container = document.getElementById(containerId);
        if (!btn || !container) return;

        btn.addEventListener('click', function () {
            const nextIdx = container.children.length;
            const div = document.createElement('div');
            div.className = 'km-item-row';
            
            if (isPercent) {
                div.innerHTML = `
                    <input type="text" name="${prefix}[${nextIdx}][name]" placeholder="Novo canal..." class="km-input-text" required>
                    <div class="km-input-percent">
                        <input type="number" name="${prefix}[${nextIdx}][tax_percent]" value="0.0" step="0.1" min="0" max="90" class="km-input-num" required>
                        <span>%</span>
                    </div>
                    <button type="button" class="km-remove-row-btn" title="Remover">✕</button>
                `;
            } else {
                const calcClass = prefix === 'labor_items' ? 'km-calc-labor' : (prefix === 'fixed_expenses' ? 'km-calc-fixed' : 'km-calc-var');
                div.innerHTML = `
                    <input type="text" name="${prefix}[${nextIdx}][name]" placeholder="Novo item..." class="km-input-text" required>
                    <div class="km-input-currency">
                        <span>R$</span>
                        <input type="number" name="${prefix}[${nextIdx}][value]" value="0.00" step="0.1" min="0" class="km-input-num ${calcClass}" required>
                    </div>
                    <button type="button" class="km-remove-row-btn" title="Remover">✕</button>
                `;
            }

            container.appendChild(div);
            bindRow(div);
            recalculateKpis();
        });
    }

    setupAddButton('km-add-labor-btn', 'km-labor-list', 'labor_items', false);
    setupAddButton('km-add-fixed-btn', 'km-fixed-list', 'fixed_expenses', false);
    setupAddButton('km-add-var-btn', 'km-var-list', 'variable_expenses', false);
    setupAddButton('km-add-channel-btn', 'km-channel-list', 'sales_channels', true);
});
</script>