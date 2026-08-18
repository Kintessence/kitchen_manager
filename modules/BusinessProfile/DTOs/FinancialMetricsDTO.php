<?php

declare(strict_types=1);

namespace KitchenManager\Modules\BusinessProfile\DTOs;

final class FinancialMetricsDTO implements \ArrayAccess
{
    public function __construct(
        public readonly float $monthlyProductiveHours,
        public readonly float $totalStructuralCost,
        public readonly float $costPerHour,
        public readonly float $costPerMinute,
        public readonly float $markupDivisor,
        public readonly float $markupMultiplier,
        public readonly float $breakEvenRevenue,
        public readonly float $wasteMultiplier
    ) {}

    public function toArray(): array
    {
        return [
            'monthly_productive_hours' => $this->monthlyProductiveHours,
            'total_structural_cost'    => $this->totalStructuralCost,
            'cost_per_hour'            => $this->costPerHour,
            'cost_per_minute'          => $this->costPerMinute,
            'markup_divisor'           => $this->markupDivisor,
            'markup_multiplier'        => $this->markupMultiplier,
            'break_even_revenue'       => $this->breakEvenRevenue,
            'waste_multiplier'         => $this->wasteMultiplier,
            // Aliases legados comumente usados no Dashboard:
            'minute_cost'              => $this->costPerMinute,
            'hour_cost'                => $this->costPerHour,
            'total_fixed'              => $this->totalStructuralCost,
            'monthly_hours'            => $this->monthlyProductiveHours,
            'break_even'               => $this->breakEvenRevenue,
        ];
    }

    // --- Implementação de ArrayAccess para compatibilidade total ---

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
        return $this->$prop ?? 0.0;
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