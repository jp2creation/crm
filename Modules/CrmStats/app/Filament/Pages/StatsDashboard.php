<?php

namespace Modules\CrmStats\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Modules\CrmStats\Filament\Concerns\AuthorizesStatsAccess;
use Modules\CrmStats\Filament\Widgets\BillingFamilyBreakdownChart;
use Modules\CrmStats\Filament\Widgets\BillingRevenueChart;
use Modules\CrmStats\Filament\Widgets\BillingStatsOverviewWidget;
use Modules\CrmStats\Services\BillingStatsDashboardService;
use UnitEnum;

class StatsDashboard extends Page
{
    use AuthorizesStatsAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'stats';

    protected static ?string $title = 'Stats commerciales';

    protected string $view = 'crm-stats::filament.pages.dashboard';

    /**
     * @var array{preset:string}
     */
    public array $filters = [
        'preset' => 'month',
    ];

    public function setPreset(string $preset): void
    {
        if (! in_array($preset, ['today', 'month', 'previous_month', 'year'], true)) {
            return;
        }

        $this->filters['preset'] = $preset;
    }

    /**
     * @return array<class-string|WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            BillingStatsOverviewWidget::class,
        ];
    }

    /**
     * @return array<class-string|WidgetConfiguration>
     */
    protected function getFooterWidgets(): array
    {
        return [
            BillingRevenueChart::class,
            BillingFamilyBreakdownChart::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(BillingStatsDashboardService::class);
        $period = $service->period($this->filters['preset']);

        return [
            'period' => $period,
            'apiError' => $service->lastApiError(),
            'topClients' => $service->clientRows($period['start'], $period['end'], limit: 5),
            'families' => $service->familyRows($period['start'], $period['end'], limit: 5),
            'presets' => [
                'today' => 'Aujourd hui',
                'month' => 'Ce mois',
                'previous_month' => 'Mois precedent',
                'year' => 'Cette annee',
            ],
        ];
    }
}
