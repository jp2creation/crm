<?php

namespace Modules\CrmStats\Kpis;

use App\Models\CachedBillingStat;
use Elegantly\Kpi\Enums\KpiInterval;
use Elegantly\Kpi\KpiFloatDefinition;

class BillingInvoicesKpi extends KpiFloatDefinition
{
    public static function getName(): string
    {
        return 'crm_stats:billing:invoices';
    }

    public static function getLabel(): string
    {
        return 'Factures';
    }

    public static function getSnapshotInterval(): KpiInterval
    {
        return KpiInterval::Day;
    }

    public function getValue(): float
    {
        return (float) CachedBillingStat::query()
            ->when($this->date, fn ($query) => $query->where('date', '<=', $this->date?->toDateString()))
            ->sum('invoice_count');
    }
}
