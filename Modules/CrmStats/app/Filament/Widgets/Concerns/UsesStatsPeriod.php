<?php

namespace Modules\CrmStats\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Modules\CrmStats\Services\BillingStatsDashboardService;

trait UsesStatsPeriod
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $pageFilters = null;

    /**
     * @return array{start:CarbonImmutable,end:CarbonImmutable,label:string,preset:string}
     */
    protected function statsPeriod(): array
    {
        return app(BillingStatsDashboardService::class)->period(
            (string) ($this->pageFilters['preset'] ?? 'month'),
        );
    }
}
