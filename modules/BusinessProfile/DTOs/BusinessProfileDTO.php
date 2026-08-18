<?php

declare(strict_types=1);

namespace KitchenManager\Modules\BusinessProfile\DTOs;

final class BusinessProfileDTO implements \ArrayAccess
{
    public function __construct(
        public readonly float $ownerSalaryTarget,
        public readonly float $fixedExpensesTotal,
        public readonly int $workDaysPerWeek,
        public readonly float $workHoursPerDay,
        public readonly int $productionStaffCount,
        public readonly float $targetNetMargin,
        public readonly float $cardFeeRate,
        public readonly float $taxRate,
        public readonly float $ingredientWasteFactor,
        public readonly bool $setupCompleted = false
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ownerSalaryTarget: max(0.0, (float) ($data['owner_salary_target'] ?? $data['ownerSalaryTarget'] ?? 2500.00)),
            fixedExpensesTotal: max(0.0, (float) ($data['fixed_expenses_total'] ?? $data['fixedExpensesTotal'] ?? 600.00)),
            workDaysPerWeek: max(1, min(7, (int) ($data['work_days_per_week'] ?? $data['workDaysPerWeek'] ?? 5))),
            workHoursPerDay: max(0.5, min(24.0, (float) ($data['work_hours_per_day'] ?? $data['workHoursPerDay'] ?? 6.0))),
            productionStaffCount: max(1, (int) ($data['production_staff_count'] ?? $data['productionStaffCount'] ?? 1)),
            targetNetMargin: max(0.0, min(90.0, (float) ($data['target_net_margin'] ?? $data['targetNetMargin'] ?? 25.0))),
            cardFeeRate: max(0.0, min(30.0, (float) ($data['card_fee_rate'] ?? $data['cardFeeRate'] ?? 3.5))),
            taxRate: max(0.0, min(50.0, (float) ($data['tax_rate'] ?? $data['taxRate'] ?? 0.0))),
            ingredientWasteFactor: max(0.0, min(50.0, (float) ($data['ingredient_waste_factor'] ?? $data['ingredientWasteFactor'] ?? 5.0))),
            setupCompleted: (bool) ($data['setup_completed'] ?? $data['setupCompleted'] ?? false)
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
        ];
    }

    // --- Implementação de ArrayAccess ---

    public function offsetExists(mixed $offset): bool
    {
        $data = $this->toArray();
        return isset($data[$offset]) || property_exists($this, (string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $data = $this->toArray();
        if (array_key_exists($offset, $data)) {
            return $data[$offset];
        }

        $prop = (string) $offset;
        return $this->$prop ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // DTO é imutável
    }

    public function offsetUnset(mixed $offset): void
    {
        // DTO é imutável
    }
}