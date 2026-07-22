<?php

namespace Modules\CrmStats\Kpis;

use App\Models\CachedBillingStat;
use Elegantly\Kpi\Enums\KpiInterval;
use Elegantly\Kpi\KpiFloatDefinition;

class BillingRevenueKpi extends KpiFloatDefinition
{
    public static function getName(): string
    {
        return 'crm_stats:billing:revenue';
    }

    public static function getLabel(): string
    {
        return 'CA facture';
    }

    public static function getSnapshotInterval(): KpiInterval
    {
        return KpiInterval::Day;
    }

    public function getValue(): float
    {
        return (float) CachedBillingStat::query()
            ->when($this->date, fn ($query) => $query->where('date', '<=', $this->date?->toDateString()))
            ->sum('total_amount');
    }
}
