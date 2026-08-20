<?php
if (!defined('ABSPATH')) exit;

$daysMap = [
    'monday'    => 'Segunda-feira',
    'tuesday'   => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday'  => 'Quinta-feira',
    'friday'    => 'Sexta-feira',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
];

$schedule = !empty($profile->weeklySchedule) ? $profile->weeklySchedule : [
    'monday' => 8.0, 'tuesday' => 8.0, 'wednesday' => 8.0, 
    'thursday' => 8.0, 'friday' => 8.0, 'saturday' => 0.0, 'sunday' => 0.0
];

$fixedExpenses = !empty($profile->fixedExpensesList) ? $profile->fixedExpensesList : [
    ['name' => 'Energia Elétrica (Proporcional Cozinha)', 'value' => 150.00],
    ['name' => 'Gás de Cozinha / Encanado', 'value' => 120.00],
    ['name' => 'Água & Saneamento', 'value' => 50.00],
    ['name' => 'DAS / MEI ou Impostos Fixos', 'value' => 75.00],
    ['name' => 'Internet & Sistema (Kitchen Manager)', 'value' => 90.00],
];

$laborItems = !empty($profile->laborItemsList) ? $profile->laborItemsList : [
    ['name' => 'Pró-labore (Seu Salário de Gestão/Produção)', 'value' => $profile->ownerSalaryTarget ?? 2500.00]
];

$variableExpenses = !empty($profile->variableExpensesList) ? $profile->variableExpensesList : [
    ['name' => 'Sacolas, Etiquetas e Fitas Gerais', 'value' => 100.00]
];

$salesChannels = !empty($profile->salesChannelsList) ? $profile->salesChannelsList : [
    ['name' => 'Venda Direta / Pix (Balcão)', 'tax_percent' => 0.0],
    ['name' => 'Cartão de Débito', 'tax_percent' => 1.8],
    ['name' => 'Cartão de Crédito (1x)', 'tax_percent' => 3.8],
    ['name' => 'iFood (Entrega Própria)', 'tax_percent' => 12.0],
    ['name' => 'iFood (Entrega Parceira)', 'tax_percent' => 27.0]
];
?>

<div class="wrap km-admin-wrap">
    
    <div class="km-header-bar">
        <div>
            <h1 class="km-page-title">🏢 Diagnóstico & Estrutura do Negócio</h1>
            <p class="km-page-desc">Mapeie salários, despesas operacionais, ritmo de produção e taxas comerciais para alimentar a precificação automática.</p>
        </div>
        <div>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_reset_business_profile'), 'km_reset_profile_action')); ?>" 
               class="button button-secondary"
               onclick="return confirm('Deseja redefinir para os valores médios de referência de mercado? Isso apagará as configurações atuais.');">
                🔄 Restaurar Padrões de Mercado
            </a>
        </div>
    </div>

    <?php if (isset($status) && $status === 'saved'): ?>
        <div class="notice notice-success is-dismissible km-notice">
            <p><strong>✅ Estrutura do negócio atualizada com sucesso! O custo da hora produtiva, taxas e capacidade foram recalculados.</strong></p>
        </div>
    <?php elseif (isset($status) && $status === 'reset'): ?>
        <div class="notice notice-info is-dismissible km-notice">
            <p><strong>✨ Padrões de mercado carregados com sucesso! Ajuste os valores de acordo com a sua realidade.</strong></p>
        </div>
    <?php endif; ?>

    <div class="km-kpi-grid">
        <div class="km-kpi-card">
            <span class="km-kpi-title">Mão de Obra Total</span>
            <strong class="km-kpi-val" id="km-kpi-labor">R$ <?php echo number_format($metrics->totalStructuralCost ?? 0, 2, ',', '.'); ?></strong>
            <small>Pró-labore + Equipe de Produção</small>
        </div>
        <div class="km-kpi-card">
            <span class="km-kpi-title">Capacidade Mensal Produtiva</span>
            <strong class="km-kpi-val" id="km-kpi-monthly-hours" style="color: #1d2327;"><?php echo number_format($metrics->monthlyProductiveHours ?? 160, 1, ',', '.'); ?> hrs</strong>
            <small id="km-kpi-weekly-hours-lbl">Baseado na sua rotina semanal</small>
        </div>
        <div class="km-kpi-card km-kpi-highlight">
            <span class="km-kpi-title">Custo da Sua Hora de Cozinha</span>
            <strong class="km-kpi-val" id="km-kpi-hour" style="color: #2271b1;">R$ <?php echo number_format($metrics->costPerHour ?? 0, 2, ',', '.'); ?> / hora</strong>
            <small id="km-kpi-minute">(R$ <?php echo number_format($metrics->costPerMinute ?? 0, 4, ',', '.'); ?> por minuto de forno/bancada)</small>
        </div>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-profile-form">
        <input type="hidden" name="action" value="km_save_business_profile">
        <?php wp_nonce_field('km_save_profile_action', 'km_profile_nonce'); ?>

        <div class="km-split-grid">
            
            <div class="km-forms-col">
                
                <div class="km-card km-section-card">
                    <div class="km-section-header">
                        <h2 class="km-card-title">👥 1. Pessoas & Salários (Mão de Obra Real)</h2>
                        <button type="button" class="button button-small" id="km-add-labor-btn">➕ Adicionar Pessoa</button>
                    </div>
                    <p class="km-section-desc">
                        Informe o quanto você precisa tirar por mês para viver (pró-labore) e os salários ou diárias de ajudantes na cozinha.
                    </p>
                    
                    <div id="km-labor-list" class="km-repeater-container">
                        <?php foreach ($laborItems as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="labor_items[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: Pró-labore Sócio Confeiteiro" class="km-input flex-1" required>
                                <div class="km-input-addon-group">
                                    <span class="km-addon">R$</span>
                                    <input type="number" name="labor_items[<?php echo $idx; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" step="0.01" min="0" class="km-input km-calc-labor" required>
                                </div>
                                <button type="button" class="km-btn-del" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="km-card km-section-card">
                    <div class="km-section-header">
                        <h2 class="km-card-title">
                            ⏱️ 2. Ritmo de Produção & Disponibilidade da Cozinha
                            <span class="km-tooltip-trigger" data-tooltip="Insira quantas horas puras de MÃO NA MASSA você produz em cada dia. Não conte tempo de compras ou entregas.">ℹ️</span>
                        </h2>
                    </div>
                    <p class="km-section-desc">
                        Defina a carga horária de bancada/forno para cada dia da semana. Dias em que a cozinha não produz devem ficar com 0.
                    </p>

                    <div class="km-schedule-quick-actions">
                        <button type="button" class="button button-secondary button-small" id="km-btn-copy-workdays">⚡ Repetir Segunda para Dias Úteis (Seg-Sex)</button>
                        <button type="button" class="button button-secondary button-small" id="km-btn-copy-all">⚡ Copiar Segunda para Todos os Dias</button>
                    </div>

                    <div class="km-schedule-grid">
                        <?php foreach ($daysMap as $dayKey => $dayLabel): 
                            $val = $schedule[$dayKey] ?? 0.0;
                        ?>
                            <div class="km-schedule-day-item">
                                <label for="sched_<?php echo $dayKey; ?>"><?php echo esc_html($dayLabel); ?></label>
                                <div class="km-input-addon-group km-addon-right" style="width: 100%;">
                                    <input type="number" id="sched_<?php echo $dayKey; ?>" name="weekly_schedule[<?php echo $dayKey; ?>]" value="<?php echo esc_attr($val); ?>" step="0.5" min="0" max="24" class="km-input km-calc-sched" required>
                                    <span class="km-addon">hrs</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="km-grid-2col" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f0f1;">
                        <div class="km-field-group">
                            <label for="km-staff-count">
                                Pessoas Ativas na Produção:
                                <span class="km-tooltip-trigger" data-tooltip="Se você tem 2 confeiteiros trabalhando juntos nas mesmas 8 horas, sua cozinha gera 16 horas produtivas por dia.">ℹ️</span>
                            </label>
                            <input type="number" id="km-staff-count" name="production_staff_count" value="<?php echo esc_attr($profile->productionStaffCount ?? 1); ?>" step="1" min="1" max="20" class="km-input widefat" required>
                            <small>Pessoas com a mão na massa ao mesmo tempo.</small>
                        </div>
                        <div class="km-field-group">
                            <label>Total de Horas Semanais:</label>
                            <div class="km-stat-display" id="km-lbl-weekly-total-hours">0,0 hrs / semana</div>
                            <small>Soma da carga horária da equipe.</small>
                        </div>
                    </div>
                </div>

                <div class="km-card km-section-card">
                    <div class="km-section-header">
                        <h2 class="km-card-title">
                            🏠 3. Contas Fixas & Estrutura Mensal
                            <span class="km-tooltip-trigger" data-tooltip="Despesas que chegam todo mês mesmo que você não venda nada (energia, gás, internet, MEI).">ℹ️</span>
                        </h2>
                        <button type="button" class="button button-small" id="km-add-fixed-btn">➕ Adicionar Conta</button>
                    </div>
                    <p class="km-section-desc">
                        Preencha o valor aproximado mensal de cada conta da estrutura de trabalho:
                    </p>
                    
                    <div id="km-fixed-list" class="km-repeater-container">
                        <?php foreach ($fixedExpenses as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="fixed_expenses[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: Gás de Cozinha" class="km-input flex-1" required>
                                <div class="km-input-addon-group">
                                    <span class="km-addon">R$</span>
                                    <input type="number" name="fixed_expenses[<?php echo $idx; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" step="0.01" min="0" class="km-input km-calc-fixed" required>
                                </div>
                                <button type="button" class="km-btn-del" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="km-card km-section-card">
                    <h2 class="km-card-title" style="margin-bottom: 12px;">📊 4. Margem de Lucro Alvo & Segurança Técnica</h2>
                    
                    <div class="km-grid-2col">
                        <div class="km-field-group">
                            <label for="km-margin-input">
                                Margem Líquida Alvo (%):
                                <span class="km-tooltip-trigger" data-tooltip="Lucro livre no caixa da EMPRESA para crescimento e reservas, além do seu salário. O padrão saudável de mercado é 20% a 30%.">ℹ️</span>
                            </label>
                            <div class="km-input-addon-group km-addon-right">
                                <input type="number" id="km-margin-input" name="target_net_margin" value="<?php echo esc_attr($profile->targetNetMargin ?? 25.0); ?>" step="0.5" min="0" max="90" class="km-input widefat" required>
                                <span class="km-addon">%</span>
                            </div>
                            <small>Sugerido: 25% (para fundo de reserva e expansão).</small>
                        </div>

                        <div class="km-field-group">
                            <label for="km-waste-input">
                                Perda / Sobras Técnicas (%):
                                <span class="km-tooltip-trigger" data-tooltip="Margem de segurança adicionada ao custo dos insumos para cobrir raspas na panela, rebarbas e pequenas perdas de bancada.">ℹ️</span>
                            </label>
                            <div class="km-input-addon-group km-addon-right">
                                <input type="number" id="km-waste-input" name="ingredient_waste_factor" value="<?php echo esc_attr($profile->ingredientWasteFactor ?? 5.0); ?>" step="0.5" min="0" max="30" class="km-input widefat" required>
                                <span class="km-addon">%</span>
                            </div>
                            <small>Sugerido: 5% a 8% para confeitaria / padaria.</small>
                        </div>
                    </div>
                </div>

                <div class="km-card km-section-card">
                    <div class="km-section-header">
                        <h2 class="km-card-title">💳 5. Canais de Venda & Meios de Pagamento</h2>
                        <button type="button" class="button button-small" id="km-add-channel-btn">➕ Adicionar Canal</button>
                    </div>
                    <p class="km-section-desc">Taxas cobradas por maquininhas de cartão e aplicativos de entrega sobre o valor da venda.</p>
                    
                    <div id="km-channel-list" class="km-repeater-container">
                        <?php foreach ($salesChannels as $idx => $item): ?>
                            <div class="km-item-row">
                                <input type="text" name="sales_channels[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['name']); ?>" placeholder="Ex: iFood Entrega Parceira" class="km-input flex-1" required>
                                <div class="km-input-addon-group km-addon-right">
                                    <input type="number" name="sales_channels[<?php echo $idx; ?>][tax_percent]" value="<?php echo esc_attr($item['tax_percent']); ?>" step="0.1" min="0" max="90" class="km-input" required>
                                    <span class="km-addon">%</span>
                                </div>
                                <button type="button" class="km-btn-del" title="Remover">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="button button-primary button-large km-btn-save" style="width: 100%; margin-top: 8px;">
                    💾 Salvar Configurações & Recalcular Motor Financeiro
                </button>

            </div>

            <div class="km-sidebar-col">
                
                <h2 style="font-size: 16px; margin: 0 0 16px 0; color: #1d2327;">📚 Conhecimento Financeiro Embutido</h2>

                <div class="km-edu-card km-edu-info">
                    <h3>💡 Por que 25% de Margem Alvo se eu já tenho Pró-labore?</h3>
                    <p>O <strong>Pró-labore</strong> paga a sua sobrevivência pessoal (seu trabalho de confeiteiro/gestor). Já a <strong>Margem de Lucro (25%)</strong> pertence à <u>empresa</u>.</p>
                    <p style="margin-bottom: 0;">Ela serve para formar um fundo de reserva para quando uma batedeira quebrar, comprar novos utensílios e investir em divulgação.</p>
                </div>

                <div class="km-edu-card km-edu-warning">
                    <h3>🧼 Por que colocar 5% de Perda Técnica?</h3>
                    <p>Na cozinha artesanal, o leite condensado que fica raspado na caixinha, a massa grudada no bowl ou o brigadeiro que passa do ponto são custos invisíveis.</p>
                    <p style="margin-bottom: 0;">A taxa de 5% blinda suas fichas técnicas contra esse desperdício diário sem precisar pesar gota por gota.</p>
                </div>

                <div class="km-edu-card km-edu-tip">
                    <h3>👥 Reflexo de Adicionar Pessoas na Produção</h3>
                    <p>Ao adicionar ajudantes na produção, sua capacidade mensal de horas <strong>aumenta</strong>, o que pode baratear o custo por minuto.</p>
                    <p style="margin-bottom: 0;"><strong>Atenção:</strong> Lembre-se de adicionar o salário/diária do ajudante na seção de Mão de Obra para manter o custo real equilibrado!</p>
                </div>

            </div>

        </div>
    </form>
</div>

<style>
.km-admin-wrap { max-width: 1280px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.km-header-bar { background: #ffffff; padding: 18px 24px; border: 1px solid #ccd0d4; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-page-title { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; font-weight: 600; }
.km-page-desc { margin: 0; color: #646970; font-size: 14px; }
.km-notice { margin-top: 15px; margin-bottom: 20px; border-radius: 4px; border-left-width: 4px; }

.km-split-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px; align-items: start; }
.km-card { background: #ffffff; border: 1px solid #ccd0d4; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden; }

.km-kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.km-kpi-card { background: #fff; border: 1px solid #ccd0d4; padding: 18px 22px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.km-kpi-card.km-kpi-highlight { border-left: 4px solid #2271b1; background: #f0f6fc; }
.km-kpi-title { display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #50575e; margin-bottom: 4px; }
.km-kpi-val { display: block; font-size: 22px; color: #1d2327; margin-bottom: 4px; font-weight: 600; }
.km-kpi-card small { color: #646970; font-size: 12px; }

.km-section-card { padding: 22px; margin-bottom: 16px; }
.km-section-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px; margin-bottom: 8px; }
.km-card-title { font-size: 15px; margin: 0; color: #1d2327; font-weight: 600; }
.km-section-desc { font-size: 13px; color: #646970; margin-top: 0; margin-bottom: 16px; line-height: 1.4; }

.km-input { padding: 8px 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; box-sizing: border-box; }
.km-input:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.km-btn-save { height: 46px; font-size: 15px; font-weight: 600; }

.km-repeater-container { display: flex; flex-direction: column; gap: 10px; }
.km-item-row { display: flex; align-items: center; gap: 8px; }
.flex-1 { flex: 1; }
.km-btn-del { color: #b32d2e; background: none; border: none; font-weight: bold; font-size: 14px; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
.km-btn-del:hover { background: #f8d7da; }

.km-input-addon-group { display: flex; align-items: center; width: 150px; }
.km-input-addon-group .km-addon { background: #f0f0f1; border: 1px solid #8c8f94; padding: 8px 12px; font-weight: 600; color: #50575e; font-size: 13px; }
.km-input-addon-group input { flex: 1; min-width: 0; }

.km-input-addon-group:not(.km-addon-right) .km-addon { border-right: none; border-radius: 4px 0 0 4px; }
.km-input-addon-group:not(.km-addon-right) input { border-radius: 0 4px 4px 0; }
.km-input-addon-group.km-addon-right input { border-right: none; border-radius: 4px 0 0 4px; }
.km-input-addon-group.km-addon-right .km-addon { border-radius: 0 4px 4px 0; }

.km-schedule-quick-actions { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.km-schedule-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
.km-schedule-day-item { background: #f9f9f9; border: 1px solid #dcdcde; padding: 10px; border-radius: 6px; text-align: center; }
.km-schedule-day-item label { display: block; font-size: 11.5px; font-weight: 700; color: #2c3338; margin-bottom: 6px; text-transform: uppercase; }
.km-stat-display { background: #f0f6fc; border: 1px solid #cce5ff; color: #004085; padding: 8px; border-radius: 4px; font-weight: bold; font-size: 14px; text-align: center; }

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

.km-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.km-field-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #1d2327; }
.km-field-group small { display: block; color: #646970; font-size: 12px; margin-top: 4px; line-height: 1.3; }

.km-sidebar-col { display: flex; flex-direction: column; gap: 14px; }
.km-edu-card { background: #fff; border: 1px solid #ccd0d4; padding: 18px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.km-edu-card h3 { margin: 0 0 10px 0; font-size: 14px; font-weight: 700; }
.km-edu-card p { font-size: 12.5px; line-height: 1.5; color: #2c3338; margin: 0 0 10px 0; }

.km-edu-warning { border-left: 4px solid #dba617; background: #fcf9e8; }
.km-edu-info { border-left: 4px solid #2271b1; background: #f0f6fc; }
.km-edu-tip { border-left: 4px solid #007017; background: #f4fbf5; }

@media (max-width: 960px) {
    .km-split-grid { grid-template-columns: 1fr; }
    .km-kpi-grid { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kpiLabor        = document.getElementById('km-kpi-labor');
    const kpiMonthlyHours = document.getElementById('km-kpi-monthly-hours');
    const kpiHour         = document.getElementById('km-kpi-hour');
    const kpiMinute       = document.getElementById('km-kpi-minute');
    const staffInput      = document.getElementById('km-staff-count');
    const weeklyTotalLbl  = document.getElementById('km-lbl-weekly-total-hours');
    const form            = document.getElementById('km-profile-form');

    form.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
            event.preventDefault();
        }
    });

    function recalculateKpis() {
        let totalLabor = 0;
        document.querySelectorAll('.km-calc-labor').forEach(el => totalLabor += parseFloat(el.value) || 0);

        let totalFixed = 0;
        document.querySelectorAll('.km-calc-fixed').forEach(el => totalFixed += parseFloat(el.value) || 0);

        const totalStructure = totalLabor + totalFixed;

        let weeklyHours = 0;
        document.querySelectorAll('.km-calc-sched').forEach(el => weeklyHours += parseFloat(el.value) || 0);

        const staffCount = parseInt(staffInput.value) || 1;
        const totalWeeklyTeamHours = weeklyHours * staffCount;
        const monthlyHours = Math.max(1.0, totalWeeklyTeamHours * 4.3333);

        const costPerHour = monthlyHours > 0 ? (totalStructure / monthlyHours) : 0;
        const costPerMinute = costPerHour / 60;

        kpiLabor.textContent = 'R$ ' + totalStructure.toFixed(2).replace('.', ',');
        kpiMonthlyHours.textContent = monthlyHours.toFixed(1).replace('.', ',') + ' hrs';
        kpiHour.textContent = 'R$ ' + costPerHour.toFixed(2).replace('.', ',') + ' / hora';
        kpiMinute.textContent = '(R$ ' + costPerMinute.toFixed(4).replace('.', ',') + ' por minuto de forno/bancada)';
        weeklyTotalLbl.textContent = totalWeeklyTeamHours.toFixed(1).replace('.', ',') + ' hrs / semana';
    }

    function bindRow(row) {
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', recalculateKpis);
        });
        const removeBtn = row.querySelector('.km-btn-del');
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
    document.querySelectorAll('.km-calc-sched').forEach(el => el.addEventListener('input', recalculateKpis));
    staffInput.addEventListener('input', recalculateKpis);

    document.getElementById('km-btn-copy-workdays').addEventListener('click', function() {
        const mondayVal = parseFloat(document.getElementById('sched_monday').value) || 0;
        ['tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
            document.getElementById('sched_' + day).value = mondayVal;
        });
        recalculateKpis();
    });

    document.getElementById('km-btn-copy-all').addEventListener('click', function() {
        const mondayVal = parseFloat(document.getElementById('sched_monday').value) || 0;
        ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(day => {
            document.getElementById('sched_' + day).value = mondayVal;
        });
        recalculateKpis();
    });

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
                    <input type="text" name="${prefix}[${nextIdx}][name]" placeholder="Novo canal..." class="km-input flex-1" required>
                    <div class="km-input-addon-group km-addon-right">
                        <input type="number" name="${prefix}[${nextIdx}][tax_percent]" value="0.0" step="0.1" min="0" max="90" class="km-input" required>
                        <span class="km-addon">%</span>
                    </div>
                    <button type="button" class="km-btn-del" title="Remover">✕</button>
                `;
            } else {
                const calcClass = prefix === 'labor_items' ? 'km-calc-labor' : 'km-calc-fixed';
                div.innerHTML = `
                    <input type="text" name="${prefix}[${nextIdx}][name]" placeholder="Novo item..." class="km-input flex-1" required>
                    <div class="km-input-addon-group">
                        <span class="km-addon">R$</span>
                        <input type="number" name="${prefix}[${nextIdx}][value]" value="0.00" step="0.01" min="0" class="km-input ${calcClass}" required>
                    </div>
                    <button type="button" class="km-btn-del" title="Remover">✕</button>
                `;
            }

            container.appendChild(div);
            bindRow(div);
            recalculateKpis();
        });
    }

    setupAddButton('km-add-labor-btn', 'km-labor-list', 'labor_items', false);
    setupAddButton('km-add-fixed-btn', 'km-fixed-list', 'fixed_expenses', false);
    setupAddButton('km-add-channel-btn', 'km-channel-list', 'sales_channels', true);

    recalculateKpis();
});
</script>