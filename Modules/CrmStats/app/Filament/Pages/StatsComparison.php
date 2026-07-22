<?php

namespace Modules\CrmStats\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\CrmStats\Filament\Concerns\AuthorizesStatsAccess;
use Modules\CrmStats\Services\BillingStatsDashboardService;
use UnitEnum;

class StatsComparison extends Page
{
    use AuthorizesStatsAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $navigationLabel = 'Comparaison';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'stats/comparaison';

    protected static ?string $title = 'Comparaison commerciale';

    protected string $view = 'crm-stats::filament.pages.comparison';

    /**
     * @var array{preset:string,left_metric:string,right_metric:string}
     */
    public array $filters = [
        'preset' => 'month',
        'left_metric' => 'total_revenue',
        'right_metric' => 'invoice_count',
    ];

    public function setPreset(string $preset): void
    {
        if (in_array($preset, ['today', 'month', 'previous_month', 'year'], true)) {
            $this->filters['preset'] = $preset;
        }
    }

    public function setMetric(string $side, string $metric): void
    {
        if (! in_array($side, ['left_metric', 'right_metric'], true)) {
            return;
        }

        if (! array_key_exists($metric, $this->metrics())) {
            return;
        }

        $this->filters[$side] = $metric;
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
            'comparison' => $service->compare($this->filters['left_metric'], $this->filters['right_metric'], $period['start'], $period['end']),
            'metrics' => $this->metrics(),
            'presets' => [
                'today' => 'Aujourd hui',
                'month' => 'Ce mois',
                'previous_month' => 'Mois precedent',
                'year' => 'Cette annee',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function metrics(): array
    {
        return [
            'total_revenue' => 'Chiffre d affaires',
            'invoice_count' => 'Nombre de factures',
            'new_clients' => 'Nouveaux clients',
            'lost_clients' => 'Clients perdus',
            'average_basket' => 'Panier moyen',
            'conversion_rate' => 'Taux de conversion',
        ];
    }
}
