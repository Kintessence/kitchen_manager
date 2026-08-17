<?php

namespace KitchenManager\Modules\BusinessProfile;

namespace KitchenManager\Modules\BusinessProfile\Services;

class BusinessProfileService 
{
    private const OPTION_KEY = 'km_business_profile_data';

    public function getDefaultProfile(): array 
    {
        return [
            'labor_items' => [
                ['name' => 'Pró-labore Sócio / Confeiteiro(a)', 'value' => 2500.00],
                ['name' => 'Ajudante / Meio Período', 'value' => 800.00],
            ],
            'fixed_expenses' => [
                ['name' => 'Energia Elétrica (Fornos/Freezers)', 'value' => 240.00],
                ['name' => 'Gás de Cozinha (Média 1.5 botijões)', 'value' => 180.00],
                ['name' => 'Água & Saneamento', 'value' => 75.00],
                ['name' => 'Internet & Telefone (WhatsApp Loja)', 'value' => 110.00],
                ['name' => 'Contabilidade / MEI (DAS)', 'value' => 75.00],
                ['name' => 'Sistemas & Ferramentas Digitais', 'value' => 60.00],
            ],
            'variable_expenses' => [
                ['name' => 'Sacolas Kraft & Fitas de Cetim', 'value' => 80.00, 'unit' => 'mês'],
                ['name' => 'Etiquetas / Adesivos Lacre', 'value' => 45.00, 'unit' => 'mês'],
                ['name' => 'Gelo Reutilizável & Isopor P/ Entregas', 'value' => 50.00, 'unit' => 'mês'],
            ],
            'sales_channels' => [
                ['name' => 'Balcão / Dinheiro / Pix', 'tax_percent' => 0.0],
                ['name' => 'Cartão de Débito', 'tax_percent' => 1.8],
                ['name' => 'Cartão de Crédito à Vista (1x)', 'tax_percent' => 3.8],
                ['name' => 'iFood (Plano Básico - Entrega Própria)', 'tax_percent' => 12.0],
                ['name' => 'iFood (Entrega Parceira / Logística)', 'tax_percent' => 27.0],
            ],
            'tax_regime_percent' => 0.0, // MEI = 0% extra na nota / Simples ~4.0%
            'monthly_productive_hours' => 160.0, // 8h/dia x 20 dias úteis
            'target_net_margin' => 30.0, // Margem de lucro limpa
        ];
    }

    public function getProfile(): array 
    {
        $data = get_option(self::OPTION_KEY, null);
        if ($data === null || !is_array($data)) {
            return $this->getDefaultProfile();
        }
        return wp_parse_args($data, $this->getDefaultProfile());
    }

    public function saveProfile(array $raw): bool 
    {
        $profile = [
            'labor_items' => [],
            'fixed_expenses' => [],
            'variable_expenses' => [],
            'sales_channels' => [],
            'tax_regime_percent' => isset($raw['tax_regime_percent']) ? max(0, (float) $raw['tax_regime_percent']) : 0.0,
            'monthly_productive_hours' => isset($raw['monthly_productive_hours']) && (float) $raw['monthly_productive_hours'] > 0 ? (float) $raw['monthly_productive_hours'] : 160.0,
            'target_net_margin' => isset($raw['target_net_margin']) ? max(0, min(90, (float) $raw['target_net_margin'])) : 30.0,
        ];

        // Processa Mão de Obra
        if (!empty($raw['labor_items']) && is_array($raw['labor_items'])) {
            foreach ($raw['labor_items'] as $item) {
                $name = sanitize_text_field($item['name'] ?? '');
                $val  = (float) ($item['value'] ?? 0.0);
                if (!empty($name) && $val >= 0) {
                    $profile['labor_items'][] = ['name' => $name, 'value' => $val];
                }
            }
        }

        // Processa Custos Fixos
        if (!empty($raw['fixed_expenses']) && is_array($raw['fixed_expenses'])) {
            foreach ($raw['fixed_expenses'] as $item) {
                $name = sanitize_text_field($item['name'] ?? '');
                $val  = (float) ($item['value'] ?? 0.0);
                if (!empty($name) && $val >= 0) {
                    $profile['fixed_expenses'][] = ['name' => $name, 'value' => $val];
                }
            }
        }

        // Processa Custos Variáveis
        if (!empty($raw['variable_expenses']) && is_array($raw['variable_expenses'])) {
            foreach ($raw['variable_expenses'] as $item) {
                $name = sanitize_text_field($item['name'] ?? '');
                $val  = (float) ($item['value'] ?? 0.0);
                $unit = sanitize_text_field($item['unit'] ?? 'mês');
                if (!empty($name) && $val >= 0) {
                    $profile['variable_expenses'][] = ['name' => $name, 'value' => $val, 'unit' => $unit];
                }
            }
        }

        // Processa Canais de Venda
        if (!empty($raw['sales_channels']) && is_array($raw['sales_channels'])) {
            foreach ($raw['sales_channels'] as $item) {
                $name = sanitize_text_field($item['name'] ?? '');
                $tax  = (float) ($item['tax_percent'] ?? 0.0);
                if (!empty($name)) {
                    $profile['sales_channels'][] = ['name' => $name, 'tax_percent' => max(0, min(90, $tax))];
                }
            }
        }

        return update_option(self::OPTION_KEY, $profile);
    }

    public function resetToDefaults(): bool 
    {
        return update_option(self::OPTION_KEY, $this->getDefaultProfile());
    }

    public function calculateTotals(array $profile): array 
    {
        $totalLabor = array_sum(array_column($profile['labor_items'], 'value'));
        $totalFixed = array_sum(array_column($profile['fixed_expenses'], 'value'));
        $totalVariable = array_sum(array_column($profile['variable_expenses'], 'value'));
        $totalStructure = $totalLabor + $totalFixed + $totalVariable;

        $hours = $profile['monthly_productive_hours'] > 0 ? $profile['monthly_productive_hours'] : 160.0;
        $costPerHour = $hours > 0 ? ($totalStructure / $hours) : 0.0;
        $costPerMinute = $costPerHour / 60.0;

        return [
            'total_labor' => $totalLabor,
            'total_fixed' => $totalFixed,
            'total_variable' => $totalVariable,
            'total_structure' => $totalStructure,
            'cost_per_hour' => round($costPerHour, 2),
            'cost_per_minute' => round($costPerMinute, 4),
        ];
    }
}