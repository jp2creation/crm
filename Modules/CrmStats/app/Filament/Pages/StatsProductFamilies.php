<?php

namespace Modules\CrmStats\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Modules\CrmStats\Filament\Concerns\AuthorizesStatsAccess;
use Modules\CrmStats\Filament\Widgets\BillingFamilyBreakdownChart;
use Modules\CrmStats\Services\BillingStatsDashboardService;
use UnitEnum;

class StatsProductFamilies extends Page
{
    use AuthorizesStatsAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $navigationLabel = 'Familles produits';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'stats/familles-produits';

    protected static ?string $title = 'Stats par famille de produits';

    protected string $view = 'crm-stats::filament.pages.product-families';

    /**
     * @var array{preset:string}
     */
    public array $filters = [
        'preset' => 'month',
    ];

    public function setPreset(string $preset): void
    {
        if (in_array($preset, ['today', 'month', 'previous_month', 'year'], true)) {
            $this->filters['preset'] = $preset;
        }
    }

    /**
     * @return array<class-string|WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [
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
            'families' => $service->familyRows($period['start'], $period['end']),
            'presets' => [
                'today' => 'Aujourd hui',
                'month' => 'Ce mois',
                'previous_month' => 'Mois precedent',
                'year' => 'Cette annee',
            ],
        ];
    }
}
