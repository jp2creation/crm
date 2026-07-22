<?php

namespace Modules\CrmStats\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Modules\CrmStats\Filament\Concerns\AuthorizesStatsAccess;
use Modules\CrmStats\Filament\Widgets\Concerns\UsesStatsPeriod;
use Modules\CrmStats\Services\BillingStatsDashboardService;

class BillingStatsOverviewWidget extends StatsOverviewWidget
{
    use AuthorizesStatsAccess;
    use UsesStatsPeriod;

    protected ?string $heading = 'Indicateurs commerciaux';

    protected ?string $description = 'Donnees servies depuis les agregats locaux.';

    protected function getStats(): array
    {
        $period = $this->statsPeriod();
        $summary = app(BillingStatsDashboardService::class)->summary($period['start'], $period['end']);

        return [
            Stat::make('Chiffre d affaires', Number::currency($summary['total_revenue'], 'EUR', locale: 'fr'))
                ->icon(Heroicon::OutlinedCurrencyEuro)
                ->color('primary'),
            Stat::make('Nouveaux clients', (string) $summary['new_clients'])
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('success'),
            Stat::make('Clients perdus', (string) $summary['lost_clients'])
                ->icon(Heroicon::OutlinedUserMinus)
                ->color('danger'),
            Stat::make('Panier moyen', Number::currency($summary['average_basket'], 'EUR', locale: 'fr'))
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('info'),
            Stat::make('Taux de conversion', Number::percentage($summary['conversion_rate'], precision: 1, locale: 'fr'))
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->color('warning'),
        ];
    }
}
