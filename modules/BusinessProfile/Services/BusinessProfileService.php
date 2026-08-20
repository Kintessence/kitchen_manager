<?php

declare(strict_types=1);

namespace KitchenManager\Modules\BusinessProfile\Services;

use KitchenManager\Modules\BusinessProfile\DTOs\BusinessProfileDTO;
use KitchenManager\Modules\BusinessProfile\DTOs\FinancialMetricsDTO;
use KitchenManager\Modules\BusinessProfile\Repositories\BusinessProfileRepository;

class BusinessProfileService
{
    private const WEEKS_PER_MONTH = 4.3333;
    private const DEFAULT_ESTIMATED_CMV = 35.0;

    public function __construct(
        private readonly BusinessProfileRepository $repository = new BusinessProfileRepository()
    ) {}

    public function getProfile(): BusinessProfileDTO
    {
        return $this->repository->get();
    }

    public function saveProfileFromInput(array $input): BusinessProfileDTO
    {
        $weeklySchedule = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        $activeDays = 0;
        $totalHours = 0.0;
        foreach ($days as $day) {
            $val = isset($input['weekly_schedule'][$day]) ? (float) $input['weekly_schedule'][$day] : 0.0;
            $val = max(0.0, min(24.0, $val));
            $weeklySchedule[$day] = $val;
            if ($val > 0) {
                $activeDays++;
                $totalHours += $val;
            }
        }

        $workDaysPerWeek = $activeDays > 0 ? $activeDays : 5;
        $workHoursPerDay = $activeDays > 0 ? ($totalHours / $activeDays) : 8.0;

        $dto = new BusinessProfileDTO(
            ownerSalaryTarget: $this->parseNumber($input['owner_salary_target'] ?? 2500.0),
            fixedExpensesTotal: $this->parseNumber($input['fixed_expenses_total'] ?? 600.0),
            workDaysPerWeek: $workDaysPerWeek,
            workHoursPerDay: $workHoursPerDay,
            productionStaffCount: (int) ($input['production_staff_count'] ?? 1),
            targetNetMargin: $this->parseNumber($input['target_net_margin'] ?? 25.0),
            cardFeeRate: $this->parseNumber($input['card_fee_rate'] ?? 3.5),
            taxRate: $this->parseNumber($input['tax_rate'] ?? 0.0),
            ingredientWasteFactor: $this->parseNumber($input['ingredient_waste_factor'] ?? 5.0),
            setupCompleted: true,
            fixedExpensesList: $this->cleanRepeater($input['fixed_expenses'] ?? []),
            variableExpensesList: $this->cleanRepeater($input['variable_expenses'] ?? []),
            laborItemsList: $this->cleanRepeater($input['labor_items'] ?? []),
            salesChannelsList: $this->cleanRepeater($input['sales_channels'] ?? []),
            weeklySchedule: $weeklySchedule
        );

        $this->repository->save($dto);
        return $dto;
    }

    public function calculateMetrics(?BusinessProfileDTO $profile = null): FinancialMetricsDTO
    {
        $p = $profile ?? $this->getProfile();

        $weeklySchedule = !empty($p->weeklySchedule) ? $p->weeklySchedule : [];
        if (!empty($weeklySchedule)) {
            $hoursPerWeek = array_sum($weeklySchedule);
        } else {
            $hoursPerWeek = $p->workDaysPerWeek * $p->workHoursPerDay;
        }

        $monthlyHours = max(1.0, $hoursPerWeek * self::WEEKS_PER_MONTH * $p->productionStaffCount);

        $totalStructuralCost = $p->ownerSalaryTarget + $p->fixedExpensesTotal;
        $costPerHour = $totalStructuralCost / $monthlyHours;
        $costPerMinute = $costPerHour / 60.0;

        $totalVariableRates = $p->taxRate + $p->cardFeeRate + $p->targetNetMargin;
        $effectiveRates = min(95.0, max(0.0, $totalVariableRates));
        
        $markupDivisor = max(0.05, 1.0 - ($effectiveRates / 100.0));
        $markupMultiplier = 1.0 / $markupDivisor;

        $operationalVariableRates = $p->taxRate + $p->cardFeeRate + self::DEFAULT_ESTIMATED_CMV;
        $contributionMarginPct = max(5.0, 100.0 - $operationalVariableRates);
        $breakEvenRevenue = $totalStructuralCost / ($contributionMarginPct / 100.0);

        $wasteMultiplier = 1.0 + ($p->ingredientWasteFactor / 100.0);

        return new FinancialMetricsDTO(
            monthlyProductiveHours: round($monthlyHours, 2),
            totalStructuralCost: round($totalStructuralCost, 2),
            costPerHour: round($costPerHour, 2),
            costPerMinute: round($costPerMinute, 4),
            markupDivisor: round($markupDivisor, 4),
            markupMultiplier: round($markupMultiplier, 4),
            breakEvenRevenue: round($breakEvenRevenue, 2),
            wasteMultiplier: round($wasteMultiplier, 4)
        );
    }

    private function parseNumber(mixed $val): float
    {
        if (is_numeric($val)) return (float) $val;
        $cleaned = preg_replace('/[^\d,\.]/', '', (string) $val);
        $cleaned = str_replace(',', '.', (string) $cleaned);
        return max(0.0, (float) $cleaned);
    }

    private function cleanRepeater(array $items): array
    {
        $cleaned = [];
        foreach ($items as $item) {
            if (!empty($item['name'])) {
                $cleaned[] = $item;
            }
        }
        return $cleaned;
    }
}