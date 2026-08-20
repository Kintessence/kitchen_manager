<?php
/**
 * View: Onboarding & Setup Financeiro do Negócio
 * @var \KitchenManager\Modules\BusinessProfile\DTOs\BusinessProfileDTO $profile
 * @var \KitchenManager\Modules\BusinessProfile\DTOs\FinancialMetricsDTO $metrics
 * @var string $status
 */

$daysMap = [
    'monday'    => 'Segunda',
    'tuesday'   => 'Terça',
    'wednesday' => 'Quarta',
    'thursday'  => 'Quinta',
    'friday'    => 'Sexta',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
];

$schedule = !empty($profile->weeklySchedule) ? $profile->weeklySchedule : [
    'monday' => 8.0, 'tuesday' => 8.0, 'wednesday' => 8.0, 
    'thursday' => 8.0, 'friday' => 8.0, 'saturday' => 0.0, 'sunday' => 0.0
];
?>
<div class="wrap km-onboarding-wrap">
    <style>
        .km-onboarding-wrap { max-width: 920px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif; }
        .km-card { background: #fff; border-radius: 12px; border: 1px solid #dcdcde; box-shadow: 0 4px 15px rgba(0,0,0,0.04); padding: 32px; margin-bottom: 20px; }
        .km-wizard-steps { display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; }
        .km-step-badge { flex: 1; text-align: center; position: relative; cursor: pointer; }
        .km-step-badge .circle { width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #e0e0e0; color: #50575e; display: inline-block; font-weight: 700; margin-bottom: 8px; transition: all 0.3s ease; }
        .km-step-badge.active .circle { background: #2271b1; color: #fff; box-shadow: 0 0 0 4px rgba(34,113,177,0.2); }
        .km-step-badge.completed .circle { background: #00a32a; color: #fff; }
        .km-step-title { font-size: 13px; font-weight: 600; color: #646970; display: block; }
        .km-step-badge.active .km-step-title { color: #1d2327; font-weight: 700; }
        .km-step-pane { display: none; }
        .km-step-pane.active { display: block; animation: fadeIn 0.25s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        
        .km-input-group { margin-bottom: 22px; }
        .km-input-group label { display: block; font-weight: 700; font-size: 14px; margin-bottom: 6px; color: #1d2327; }
        .km-input-group p.desc { margin: 0 0 10px; font-size: 13px; color: #646970; line-height: 1.4; }
        .km-input-group input, .km-input-group select { width: 100%; max-width: 400px; padding: 10px 14px; font-size: 15px; border-radius: 6px; border: 1px solid #8c8f94; box-sizing: border-box; }
        
        /* Grid de Horários Semanal */
        .km-schedule-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 10px; }
        .km-day-box { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 8px; padding: 10px 6px; text-align: center; }
        .km-day-box span { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #50575e; margin-bottom: 6px; }
        .km-day-box input { width: 100% !important; text-align: center; font-weight: bold; padding: 6px 2px !important; font-size: 14px !important; }
        
        /* Quick Pills / Atalhos */
        .km-quick-pills { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .km-quick-pills button { background: #f0f0f1; border: 1px solid #c3c4c7; padding: 6px 12px; border-radius: 20px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s; }
        .km-quick-pills button:hover { background: #2271b1; color: #fff; border-color: #2271b1; }
        
        /* Tooltips */
        .km-tooltip { display: inline-block; cursor: help; color: #2271b1; font-weight: bold; position: relative; margin-left: 4px; }
        .km-tooltip:hover::after {
            content: attr(data-tip); position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%);
            background: #1d2327; color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 11.5px;
            line-height: 1.4; width: 230px; z-index: 999; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            font-weight: normal; text-align: left;
        }
        .km-tooltip:hover::before {
            content: ""; position: absolute; bottom: 105%; left: 50%; transform: translateX(-50%);
            border-width: 5px; border-style: solid; border-color: #1d2327 transparent transparent transparent; z-index: 999;
        }

        .km-actions { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f1; }
        .km-feedback-box { background: linear-gradient(135deg, #1d2327, #2c3338); color: #fff; border-radius: 12px; padding: 25px; margin-top: 30px; }
        .km-metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; }
        .km-metric-card { background: rgba(255,255,255,0.08); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); }
        .km-metric-card .val { font-size: 22px; font-weight: 800; color: #72aee6; transition: color 0.3s ease; }
        .km-metric-card .lbl { font-size: 12px; text-transform: uppercase; color: #dcdcde; margin-top: 4px; }
        
        @media (max-width: 768px) {
            .km-schedule-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>

    <?php if ($status === 'saved'): ?>
        <div class="notice notice-success is-dismissible" style="margin-bottom: 20px;"><p><strong>✅ Perfil financeiro e motor operacional atualizados com sucesso!</strong></p></div>
    <?php endif; ?>

    <div class="km-card">
        <h1 style="font-size: 24px; margin: 0 0 8px; font-weight: 800; color: #1d2327;">⚙️ Configuração Financeira do Negócio</h1>
        <p style="color: #646970; margin-bottom: 25px;">Mapeie sua estrutura de custos e ritmo de cozinha para calcular o custo do seu minuto e margem real.</p>

        <div class="km-wizard-steps" id="km-steps-header">
            <div class="km-step-badge active" data-step="1">
                <span class="circle">1</span>
                <span class="km-step-title">Seu Salário</span>
            </div>
            <div class="km-step-badge" data-step="2">
                <span class="circle">2</span>
                <span class="km-step-title">Custos Fixos</span>
            </div>
            <div class="km-step-badge" data-step="3">
                <span class="circle">3</span>
                <span class="km-step-title">Ritmo Semanal</span>
            </div>
            <div class="km-step-badge" data-step="4">
                <span class="circle">4</span>
                <span class="km-step-title">Taxas & Lucro</span>
            </div>
        </div>

        <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="km-onboarding-form">
            <?php wp_nonce_field('km_save_profile_nonce', 'km_profile_nonce'); ?>
            <input type="hidden" name="action" value="km_save_profile">

            <div class="km-step-pane active" id="km-pane-1">
                <div class="km-input-group">
                    <label for="owner_salary_target">
                        Pró-labore Mensal Desejado (R$)
                        <span class="km-tooltip" data-tip="O seu salário como confeiteiro/gestor. O trabalho do dono é custo de mão de obra e deve ser pago antes do lucro da empresa.">ℹ️</span>
                    </label>
                    <p class="desc">Quanto você precisa retirar todo mês para cobrir suas despesas pessoais com dignidade?</p>
                    <input type="number" step="50" min="0" name="owner_salary_target" id="owner_salary_target" value="<?php echo esc_attr($profile->ownerSalaryTarget); ?>" required>
                    <div class="km-quick-pills">
                        <button type="button" onclick="setSalary(2000)">R$ 2.000 (Início)</button>
                        <button type="button" onclick="setSalary(3000)">R$ 3.000 (Médio)</button>
                        <button type="button" onclick="setSalary(5000)">R$ 5.000 (Consolidado)</button>
                    </div>
                </div>
            </div>

            <div class="km-step-pane" id="km-pane-2">
                <div class="km-input-group">
                    <label for="fixed_expenses_total">
                        Custos Fixos Operacionais Mensais (R$)
                        <span class="km-tooltip" data-tip="Contas que chegam todo mês mesmo que você não venda nada: gás, energia proporcional, água, DAS do MEI e internet.">ℹ️</span>
                    </label>
                    <p class="desc">Soma aproximada das contas do seu ateliê ou espaço de produção:</p>
                    <input type="number" step="10" min="0" name="fixed_expenses_total" id="fixed_expenses_total" value="<?php echo esc_attr($profile->fixedExpensesTotal); ?>" required>
                    <div class="km-quick-pills">
                        <button type="button" onclick="setFixed(400)">💡 R$ 400 (Produção em Casa: Gás + Luz + MEI)</button>
                        <button type="button" onclick="setFixed(850)">🏠 R$ 850 (Espaço Pequeno / Ateliê)</button>
                        <button type="button" onclick="setFixed(1800)">🏢 R$ 1.800 (Loja / Cozinha Comercial)</button>
                    </div>
                </div>
            </div>

            <div class="km-step-pane" id="km-pane-3">
                <div class="km-input-group">
                    <label>
                        Horas de Mão na Massa por Dia da Semana (Forno / Bancada)
                        <span class="km-tooltip" data-tip="Coloque apenas as horas em que você realmente produz. Dias de folga deixe com 0. Isso define a capacidade mensal de minutos.">ℹ️</span>
                    </label>
                    <p class="desc">Defina quantas horas você trabalha em cada dia da semana:</p>
                    
                    <div class="km-quick-pills" style="margin-bottom: 12px;">
                        <button type="button" onclick="applyScheduleWorkdays(8)">⚡ 8h de Seg a Sex (40h/sem)</button>
                        <button type="button" onclick="applyScheduleWorkdays(6)">⚡ 6h de Seg a Sex (30h/sem)</button>
                        <button type="button" onclick="applyScheduleAll(8)">⚡ 8h Todos os Dias (Seg a Dom)</button>
                        <button type="button" onclick="applyScheduleAll(0)">🗑️ Zerar</button>
                    </div>

                    <div class="km-schedule-grid">
                        <?php foreach ($daysMap as $dayKey => $dayLabel): 
                            $val = $schedule[$dayKey] ?? 0.0;
                        ?>
                            <div class="km-day-box">
                                <span><?php echo esc_html($dayLabel); ?></span>
                                <input type="number" step="0.5" min="0" max="24" name="weekly_schedule[<?php echo $dayKey; ?>]" id="sched_<?php echo $dayKey; ?>" value="<?php echo esc_attr($val); ?>" class="km-sched-input" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="km-input-group" style="margin-top: 20px;">
                    <label for="production_staff_count">
                        Pessoas Ativas na Produção
                        <span class="km-tooltip" data-tip="Quantas pessoas trabalham juntas na cozinha ao mesmo tempo? Se você tem 1 ajudante, são 2 pessoas gerando horas produtivas simultâneas.">ℹ️</span>
                    </label>
                    <p class="desc">Você produz sozinho(a) ou tem ajudantes/confeiteiros?</p>
                    <input type="number" step="1" min="1" max="20" name="production_staff_count" id="production_staff_count" value="<?php echo esc_attr($profile->productionStaffCount); ?>" required>
                </div>
            </div>

            <div class="km-step-pane" id="km-pane-4">
                <div class="km-input-group">
                    <label for="target_net_margin">
                        Margem de Lucro Alvo da Empresa (%)
                        <span class="km-tooltip" data-tip="Por que sugerimos 25%? O seu pró-labore paga seu salário. Esses 25% pertencem à empresa para reservas, batedeiras novas e marketing.">ℹ️</span>
                    </label>
                    <p class="desc">Percentual limpo que deve sobrar no caixa após pagar custos, insumos e taxas (Padrão de mercado: 20% a 30%).</p>
                    <input type="number" step="0.5" min="0" max="80" name="target_net_margin" id="target_net_margin" value="<?php echo esc_attr($profile->targetNetMargin); ?>" required>
                </div>

                <div class="km-input-group">
                    <label for="ingredient_waste_factor">
                        Margem de Perda / Sobras Técnicas (%)
                        <span class="km-tooltip" data-tip="Por que 5%? Leite condensado raspado na lata, rebarbas de bolo e massa na espátula são perdas invisíveis. Essa taxa protege você sem precisar pesar gota por gota.">ℹ️</span>
                    </label>
                    <p class="desc">Margem de segurança para cobrir desperdícios naturais e raspas de panela (Sugerido: 5%).</p>
                    <input type="number" step="0.5" min="0" max="30" name="ingredient_waste_factor" id="ingredient_waste_factor" value="<?php echo esc_attr($profile->ingredientWasteFactor); ?>">
                </div>

                <div class="km-input-group">
                    <label for="card_fee_rate">
                        Taxa Média de Cartão / Meios de Pagamento (%)
                        <span class="km-tooltip" data-tip="Média cobrada pela sua maquininha (débito/crédito). Ex: 3.5%.">ℹ️</span>
                    </label>
                    <p class="desc">Estimativa média das taxas bancárias das suas vendas presenciais.</p>
                    <input type="number" step="0.1" min="0" max="30" name="card_fee_rate" id="card_fee_rate" value="<?php echo esc_attr($profile->cardFeeRate); ?>">
                </div>
            </div>

            <div class="km-actions">
                <button type="button" class="button button-secondary" id="km-prev-btn" style="display: none;">← Voltar</button>
                <div></div>
                <button type="button" class="button button-primary button-large" id="km-next-btn">Próximo Passo →</button>
                <button type="submit" class="button button-primary button-large" id="km-save-btn" style="display: none; background: #00a32a; border-color: #00a32a;">Salvar Configurações e Recalcular Motor 🎉</button>
            </div>
        </form>
    </div>

    <div class="km-feedback-box">
        <h2 style="color: #fff; margin: 0 0 6px; font-size: 18px;">📊 Raio-X do seu Motor Operacional</h2>
        <p style="color: #c3c4c7; margin: 0 0 16px; font-size: 13px;">Estes coeficientes recalculam em tempo real e alimentam automaticamente as fichas técnicas e o simulador de preços:</p>
        <div class="km-metrics-grid">
            <div class="km-metric-card">
                <div class="val" id="km-metric-minute">R$ <?php echo number_format($metrics->costPerMinute, 4, ',', '.'); ?></div>
                <div class="lbl">Custo por Minuto de Cozinha</div>
            </div>
            <div class="km-metric-card">
                <div class="val" id="km-metric-hour">R$ <?php echo number_format($metrics->costPerHour, 2, ',', '.'); ?></div>
                <div class="lbl">Custo da Hora Produtiva</div>
            </div>
            <div class="km-metric-card">
                <div class="val" id="km-metric-hours"><?php echo number_format($metrics->monthlyProductiveHours, 1, ',', '.'); ?> hrs</div>
                <div class="lbl">Horas Produtivas / Mês</div>
            </div>
            <div class="km-metric-card">
                <div class="val" id="km-metric-breakeven">R$ <?php echo number_format($metrics->breakEvenRevenue, 2, ',', '.'); ?></div>
                <div class="lbl">Ponto de Equilíbrio / Mês</div>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 4;

function showStep(step) {
    document.querySelectorAll('.km-step-pane').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.km-step-badge').forEach(el => {
        const s = parseInt(el.getAttribute('data-step'));
        el.classList.remove('active');
        if (s < step) el.classList.add('completed');
        else el.classList.remove('completed');
        if (s === step) el.classList.add('active');
    });

    const targetPane = document.getElementById('km-pane-' + step);
    if (targetPane) targetPane.classList.add('active');

    document.getElementById('km-prev-btn').style.display = step > 1 ? 'inline-block' : 'none';
    document.getElementById('km-next-btn').style.display = step < totalSteps ? 'inline-block' : 'none';
    document.getElementById('km-save-btn').style.display = step === totalSteps ? 'inline-block' : 'none';
}

document.getElementById('km-next-btn').addEventListener('click', () => {
    if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
});

document.getElementById('km-prev-btn').addEventListener('click', () => {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
});

document.querySelectorAll('.km-step-badge').forEach(badge => {
    badge.addEventListener('click', () => {
        currentStep = parseInt(badge.getAttribute('data-step'));
        showStep(currentStep);
    });
});

// FUNÇÃO DE CÁLCULO DINÂMICO DO RAIO-X EM TEMPO REAL
function recalculateLiveMetrics() {
    const salaryEl     = document.getElementById('owner_salary_target');
    const fixedEl      = document.getElementById('fixed_expenses_total');
    const staffEl      = document.getElementById('production_staff_count');
    const marginEl     = document.getElementById('target_net_margin');
    const cardFeeEl    = document.getElementById('card_fee_rate');

    const metricMinute    = document.getElementById('km-metric-minute');
    const metricHour      = document.getElementById('km-metric-hour');
    const metricHours     = document.getElementById('km-metric-hours');
    const metricBreakeven = document.getElementById('km-metric-breakeven');

    const salary   = parseFloat(salaryEl.value) || 0;
    const fixed    = parseFloat(fixedEl.value) || 0;
    const staff    = Math.max(1, parseInt(staffEl.value) || 1);
    const cardFee  = parseFloat(cardFeeEl ? cardFeeEl.value : 0) || 0;
    const cmvBase  = 35.0; // 35% de CMV base estimado para gastronomia

    // 1. Soma horas da semana
    let weeklyHours = 0;
    document.querySelectorAll('.km-sched-input').forEach(input => {
        weeklyHours += parseFloat(input.value) || 0;
    });

    const totalWeeklyTeamHours = weeklyHours * staff;
    const monthlyHours = Math.max(1.0, totalWeeklyTeamHours * 4.3333);

    const totalStructural = salary + fixed;
    const costPerHour = monthlyHours > 0 ? (totalStructural / monthlyHours) : 0;
    const costPerMinute = costPerHour / 60.0;

    // Ponto de Equilíbrio
    const operationalVariableRates = cardFee + cmvBase;
    const contributionMarginPct = Math.max(5.0, 100.0 - operationalVariableRates);
    const breakEven = totalStructural / (contributionMarginPct / 100.0);

    // Atualiza os cartões na tela
    if (metricMinute) metricMinute.textContent = 'R$ ' + costPerMinute.toFixed(4).replace('.', ',');
    if (metricHour) metricHour.textContent = 'R$ ' + costPerHour.toFixed(2).replace('.', ',');
    if (metricHours) metricHours.textContent = monthlyHours.toFixed(1).replace('.', ',') + ' hrs';
    if (metricBreakeven) metricBreakeven.textContent = 'R$ ' + breakEven.toFixed(2).replace('.', ',');
}

// Vincula todos os inputs ao recalculador em tempo real
document.querySelectorAll('#km-onboarding-form input, #km-onboarding-form select').forEach(el => {
    el.addEventListener('input', recalculateLiveMetrics);
    el.addEventListener('change', recalculateLiveMetrics);
});

// Funções de atalho com recálculo automático
function setSalary(val) {
    document.getElementById('owner_salary_target').value = val;
    recalculateLiveMetrics();
}

function setFixed(val) {
    document.getElementById('fixed_expenses_total').value = val;
    recalculateLiveMetrics();
}

function applyScheduleWorkdays(val) {
    ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(d => {
        const el = document.getElementById('sched_' + d);
        if (el) el.value = val;
    });
    ['saturday', 'sunday'].forEach(d => {
        const el = document.getElementById('sched_' + d);
        if (el) el.value = 0;
    });
    recalculateLiveMetrics();
}

function applyScheduleAll(val) {
    ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(d => {
        const el = document.getElementById('sched_' + d);
        if (el) el.value = val;
    });
    recalculateLiveMetrics();
}

// Dispara o cálculo na primeira renderização
document.addEventListener('DOMContentLoaded', recalculateLiveMetrics);
</script>