<?php

namespace Modules\CrmStats\Kpis;

use App\Models\CachedBillingStat;
use Elegantly\Kpi\Enums\KpiInterval;
use Elegantly\Kpi\KpiFloatDefinition;

class BillingNewClientsKpi extends KpiFloatDefinition
{
    public static function getName(): string
    {
        return 'crm_stats:billing:new_clients';
    }

    public static function getLabel(): string
    {
        return 'Nouveaux clients';
    }

    public static function getSnapshotInterval(): KpiInterval
    {
        return KpiInterval::Day;
    }

    public function getValue(): float
    {
        return (float) CachedBillingStat::query()
            ->where('client_status', 'new')
            ->when($this->date, fn ($query) => $query->where('date', '<=', $this->date?->toDateString()))
            ->distinct('client_id')
            ->count('client_id');
    }
}
