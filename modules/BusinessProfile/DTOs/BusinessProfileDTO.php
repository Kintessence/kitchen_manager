<?php

declare(strict_types=1);

namespace KitchenManager\Modules\BusinessProfile\DTOs;

final class BusinessProfileDTO implements \ArrayAccess
{
    public function __construct(
        public readonly float $ownerSalaryTarget,
        public readonly float $fixedExpensesTotal,
        public readonly int $workDaysPerWeek = 5,
        public readonly float $workHoursPerDay = 8.0,
        public readonly int $productionStaffCount = 1,
        public readonly float $targetNetMargin = 25.0,
        public readonly float $cardFeeRate = 3.5,
        public readonly float $taxRate = 0.0,
        public readonly float $ingredientWasteFactor = 5.0,
        public readonly bool $setupCompleted = false,
        public readonly array $fixedExpensesList = [],
        public readonly array $variableExpensesList = [],
        public readonly array $laborItemsList = [],
        public readonly array $salesChannelsList = [],
        public readonly array $weeklySchedule = []
    ) {}

    public static function fromArray(array $data): self
    {
        $defaultSchedule = [
            'monday'    => 8.0,
            'tuesday'   => 8.0,
            'wednesday' => 8.0,
            'thursday'  => 8.0,
            'friday'    => 8.0,
            'saturday'  => 0.0,
            'sunday'    => 0.0,
        ];

        $daysMap = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        if (!empty($data['weekly_schedule']) && is_array($data['weekly_schedule'])) {
            $weeklySchedule = [];
            foreach ($daysMap as $day) {
                $weeklySchedule[$day] = isset($data['weekly_schedule'][$day]) ? (float) $data['weekly_schedule'][$day] : 0.0;
            }
        } else {
            $legacyDays  = isset($data['work_days_per_week']) ? (int) $data['work_days_per_week'] : 5;
            $legacyHours = isset($data['work_hours_per_day']) ? (float) $data['work_hours_per_day'] : 8.0;

            $weeklySchedule = $defaultSchedule;
            if ($legacyDays >= 0 && $legacyDays <= 7) {
                $count = 0;
                foreach ($daysMap as $day) {
                    if ($count < $legacyDays) {
                        $weeklySchedule[$day] = $legacyHours;
                    } else {
                        $weeklySchedule[$day] = 0.0;
                    }
                    $count++;
                }
            }
        }

        $activeDays = 0;
        $totalHours = 0.0;
        foreach ($weeklySchedule as $h) {
            if ((float) $h > 0) {
                $activeDays++;
                $totalHours += (float) $h;
            }
        }
        $workDaysPerWeek = $activeDays > 0 ? $activeDays : (int) ($data['work_days_per_week'] ?? 5);
        $workHoursPerDay = $activeDays > 0 ? ($totalHours / $activeDays) : (float) ($data['work_hours_per_day'] ?? 8.0);

        return new self(
            ownerSalaryTarget: max(0.0, (float) ($data['owner_salary_target'] ?? 2500.00)),
            fixedExpensesTotal: max(0.0, (float) ($data['fixed_expenses_total'] ?? 600.00)),
            workDaysPerWeek: $workDaysPerWeek,
            workHoursPerDay: $workHoursPerDay,
            productionStaffCount: max(1, (int) ($data['production_staff_count'] ?? 1)),
            targetNetMargin: max(0.0, min(90.0, (float) ($data['target_net_margin'] ?? 25.0))),
            cardFeeRate: max(0.0, min(30.0, (float) ($data['card_fee_rate'] ?? 3.5))),
            taxRate: max(0.0, min(50.0, (float) ($data['tax_rate'] ?? 0.0))),
            ingredientWasteFactor: max(0.0, min(50.0, (float) ($data['ingredient_waste_factor'] ?? 5.0))),
            setupCompleted: (bool) ($data['setup_completed'] ?? false),
            fixedExpensesList: (array) ($data['fixed_expenses'] ?? []),
            variableExpensesList: (array) ($data['variable_expenses'] ?? []),
            laborItemsList: (array) ($data['labor_items'] ?? []),
            salesChannelsList: (array) ($data['sales_channels'] ?? []),
            weeklySchedule: $weeklySchedule
        );
    }

    public function toArray(): array
    {
        return [
            'owner_salary_target'     => $this->ownerSalaryTarget,
            'fixed_expenses_total'    => $this->fixedExpensesTotal,
            'work_days_per_week'      => $this->workDaysPerWeek,
            'work_hours_per_day'      => $this->workHoursPerDay,
            'production_staff_count'  => $this->productionStaffCount,
            'target_net_margin'       => $this->targetNetMargin,
            'card_fee_rate'           => $this->cardFeeRate,
            'tax_rate'                => $this->taxRate,
            'ingredient_waste_factor' => $this->ingredientWasteFactor,
            'setup_completed'         => $this->setupCompleted,
            'fixed_expenses'          => $this->fixedExpensesList,
            'variable_expenses'       => $this->variableExpensesList,
            'labor_items'             => $this->laborItemsList,
            'sales_channels'          => $this->salesChannelsList,
            'weekly_schedule'         => $this->weeklySchedule
        ];
    }

    public function offsetExists(mixed $offset): bool {
        $data = $this->toArray();
        return isset($data[$offset]) || property_exists($this, (string) $offset);
    }

    public function offsetGet(mixed $offset): mixed {
        $data = $this->toArray();
        if (array_key_exists($offset, $data)) return $data[$offset];
        $prop = (string) $offset;
        return $this->$prop ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {}
    public function offsetUnset(mixed $offset): void {}
}