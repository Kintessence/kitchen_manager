<?php
/**
 * View: Onboarding & Setup Financeiro do Negócio
 * @var \KitchenManager\Modules\BusinessProfile\DTOs\BusinessProfileDTO $profile
 * @var \KitchenManager\Modules\BusinessProfile\DTOs\FinancialMetricsDTO $metrics
 * @var string $status
 */
?>
<div class="wrap km-onboarding-wrap">
    <style>
        .km-onboarding-wrap { max-width: 900px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif; }
        .km-card { background: #fff; border-radius: 12px; border: 1px solid #dcdcde; box-shadow: 0 4px 15px rgba(0,0,0,0.04); padding: 30px; margin-bottom: 20px; }
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
        .km-input-group p.desc { margin: 0 0 8px; font-size: 13px; color: #646970; }
        .km-input-group input, .km-input-group select { width: 100%; max-width: 400px; padding: 10px 14px; font-size: 16px; border-radius: 6px; border: 1px solid #8c8f94; }
        .km-quick-pills { display: flex; gap: 8px; margin-top: 8px; }
        .km-quick-pills button { background: #f0f0f1; border: 1px solid #c3c4c7; padding: 5px 12px; border-radius: 20px; cursor: pointer; font-size: 12px; font-weight: 600; }
        .km-quick-pills button:hover { background: #2271b1; color: #fff; border-color: #2271b1; }
        .km-actions { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f1; }
        .km-feedback-box { background: linear-gradient(135deg, #1d2327, #2c3338); color: #fff; border-radius: 12px; padding: 25px; margin-top: 30px; }
        .km-metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; }
        .km-metric-card { background: rgba(255,255,255,0.08); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); }
        .km-metric-card .val { font-size: 22px; font-weight: 800; color: #72aee6; }
        .km-metric-card .lbl { font-size: 12px; text-transform: uppercase; color: #dcdcde; margin-top: 4px; }
    </style>

    <?php if ($status === 'saved'): ?>
        <div class="notice notice-success is-dismissible"><p><strong>✅ Perfil financeiro atualizado com sucesso!</strong> Todas as métricas foram recalculadas.</p></div>
    <?php endif; ?>

    <div class="km-card">
        <h1 style="font-size: 24px; margin: 0 0 10px; font-weight: 800;">⚙️ Configuração Financeira do Negócio</h1>
        <p style="color: #646970; margin-bottom: 25px;">Defina a estrutura de custos e ritmo de trabalho da sua cozinha para precificar com precisão técnica.</p>

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
                <span class="km-step-title">Ritmo de Produção</span>
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
                    <label for="owner_salary_target">Pró-labore Mensal Desejado (R$)</label>
                    <p class="desc">Quanto você precisa retirar todo mês para pagar suas despesas pessoais com tranquilidade? O seu trabalho de gestão e produção deve ser remunerado.</p>
                    <input type="number" step="50" min="0" name="owner_salary_target" id="owner_salary_target" value="<?php echo esc_attr($profile->ownerSalaryTarget); ?>" required>
                    <div class="km-quick-pills">
                        <button type="button" onclick="setSalary(2000)">R$ 2.000</button>
                        <button type="button" onclick="setSalary(3000)">R$ 3.000</button>
                        <button type="button" onclick="setSalary(5000)">R$ 5.000</button>
                    </div>
                </div>
            </div>

            <div class="km-step-pane" id="km-pane-2">
                <div class="km-input-group">
                    <label for="fixed_expenses_total">Custos Fixos Operacionais Mensais (R$)</label>
                    <p class="desc">Soma de contas mensais que chegam independente das vendas: Parcela proporcional de luz, gás, água, DAS do MEI, internet e ferramentas.</p>
                    <input type="number" step="10" min="0" name="fixed_expenses_total" id="fixed_expenses_total" value="<?php echo esc_attr($profile->fixedExpensesTotal); ?>" required>
                    <div class="km-quick-pills">
                        <button type="button" onclick="setFixed(400)">R$ 400 (Produção Caseira)</button>
                        <button type="button" onclick="setFixed(800)">R$ 800 (Ateliê Dedicado)</button>
                    </div>
                </div>
            </div>

            <div class="km-step-pane" id="km-pane-3">
                <div class="km-input-group">
                    <label for="work_days_per_week">Dias de Produção por Semana</label>
                    <p class="desc">Quantos dias na semana o forno/bancada realmente funciona?</p>
                    <select name="work_days_per_week" id="work_days_per_week">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($profile->workDaysPerWeek, $i); ?>><?php echo $i; ?> dias por semana</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="km-input-group">
                    <label for="work_hours_per_day">Horas Efetivas de Cozinha por Dia</label>
                    <p class="desc">Apenas o tempo de mão na massa (exclua horário de entregas ou compras no mercado).</p>
                    <input type="number" step="0.5" min="1" max="24" name="work_hours_per_day" id="work_hours_per_day" value="<?php echo esc_attr($profile->workHoursPerDay); ?>" required>
                </div>
                <div class="km-input-group">
                    <label for="production_staff_count">Pessoas na Produção</label>
                    <p class="desc">Você trabalha sozinho(a) ou tem ajudantes/confeiteiros ativos?</p>
                    <input type="number" step="1" min="1" max="20" name="production_staff_count" id="production_staff_count" value="<?php echo esc_attr($profile->productionStaffCount); ?>" required>
                </div>
            </div>

            <div class="km-step-pane" id="km-pane-4">
                <div class="km-input-group">
                    <label for="target_net_margin">Margem de Lucro Livre Alvo (%)</label>
                    <p class="desc">Percentual que sobra líquido no caixa da empresa para reservas e expansão.</p>
                    <input type="number" step="0.5" min="0" max="80" name="target_net_margin" id="target_net_margin" value="<?php echo esc_attr($profile->targetNetMargin); ?>" required>
                </div>
                <div class="km-input-group">
                    <label for="card_fee_rate">Taxa Média de Meios de Pagamento / Cartão (%)</label>
                    <p class="desc">Média ponderada cobrada pelas maquininhas ou links de pagamento.</p>
                    <input type="number" step="0.1" min="0" max="30" name="card_fee_rate" id="card_fee_rate" value="<?php echo esc_attr($profile->cardFeeRate); ?>">
                </div>
                <div class="km-input-group">
                    <label for="ingredient_waste_factor">Margem de Perda / Sobras Técnicas (%)</label>
                    <p class="desc">Percentual de segurança para perdas de insumos na bancada e raspas de panela.</p>
                    <input type="number" step="0.5" min="0" max="30" name="ingredient_waste_factor" id="ingredient_waste_factor" value="<?php echo esc_attr($profile->ingredientWasteFactor); ?>">
                </div>
            </div>

            <div class="km-actions">
                <button type="button" class="button button-secondary" id="km-prev-btn" style="display: none;">← Voltar</button>
                <div></div>
                <button type="button" class="button button-primary" id="km-next-btn">Próximo Passo →</button>
                <button type="submit" class="button button-primary" id="km-save-btn" style="display: none; background: #00a32a; border-color: #00a32a;">Salvar Configurações e Recalcular Motor 🎉</button>
            </div>
        </form>
    </div>

    <div class="km-feedback-box">
        <h2 style="color: #fff; margin: 0 0 6px; font-size: 18px;">📊 Raio-X do seu Motor Operacional</h2>
        <p style="color: #c3c4c7; margin: 0 0 16px; font-size: 13px;">Estes coeficientes alimentam automaticamente as fichas técnicas e precificadores.</p>
        <div class="km-metrics-grid">
            <div class="km-metric-card">
                <div class="val">R$ <?php echo number_format($metrics->costPerMinute, 2, ',', '.'); ?></div>
                <div class="lbl">Custo por Minuto</div>
            </div>
            <div class="km-metric-card">
                <div class="val">R$ <?php echo number_format($metrics->costPerHour, 2, ',', '.'); ?></div>
                <div class="lbl">Custo da Hora Produtiva</div>
            </div>
            <div class="km-metric-card">
                <div class="val"><?php echo number_format($metrics->markupMultiplier, 2, ',', '.'); ?>x</div>
                <div class="lbl">Markup Multiplicador</div>
            </div>
            <div class="km-metric-card">
                <div class="val">R$ <?php echo number_format($metrics->breakEvenRevenue, 2, ',', '.'); ?></div>
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

function setSalary(val) {
    document.getElementById('owner_salary_target').value = val;
}
function setFixed(val) {
    document.getElementById('fixed_expenses_total').value = val;
}
</script>