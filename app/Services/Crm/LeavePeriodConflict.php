<?php

namespace App\Services\Crm;

use Carbon\CarbonImmutable;

class LeavePeriodConflict
{
    public function overlaps(
        string $startDate,
        string $endDate,
        string $period,
        string $existingStartDate,
        string $existingEndDate,
        ?string $existingPeriod,
        string $existingStatus,
    ): bool {
        if ($existingStatus === 'refused') {
            return false;
        }

        $start = $this->date($startDate);
        $end = $this->date($endDate);
        $existingStart = $this->date($existingStartDate);
        $existingEnd = $this->date($existingEndDate);

        if ($existingEnd->lt($start) || $existingStart->gt($end)) {
            return false;
        }

        if (! $start->equalTo($end) || ! $existingStart->equalTo($existingEnd) || ! $start->equalTo($existingStart)) {
            return true;
        }

        $period = $this->period($period);
        $existingPeriod = $this->period($existingPeriod);

        if ($period === 'full' || $existingPeriod === 'full') {
            return true;
        }

        return $period === $existingPeriod;
    }

    private function date(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value)->startOfDay();
    }

    private function period(?string $period): string
    {
        return in_array($period, ['morning', 'afternoon'], true) ? $period : 'full';
    }
}
