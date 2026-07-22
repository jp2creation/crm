<?php

namespace Modules\CrmStats\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\CrmStats\Filament\Concerns\AuthorizesStatsAccess;
use Modules\CrmStats\Services\BillingStatsDashboardService;
use UnitEnum;

class StatsClients extends Page
{
    use AuthorizesStatsAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'stats/clients';

    protected static ?string $title = 'Stats par client';

    protected string $view = 'crm-stats::filament.pages.clients';

    /**
     * @var array{preset:string,status:?string}
     */
    public array $filters = [
        'preset' => 'month',
        'status' => null,
    ];

    public function setStatus(?string $status): void
    {
        $this->filters['status'] = in_array($status, ['new', 'lost', 'active'], true) ? $status : null;
    }

    public function setPreset(string $preset): void
    {
        if (in_array($preset, ['today', 'month', 'previous_month', 'year'], true)) {
            $this->filters['preset'] = $preset;
        }
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
            'clients' => $service->clientRows($period['start'], $period['end'], $this->filters['status']),
            'presets' => [
                'today' => 'Aujourd hui',
                'month' => 'Ce mois',
                'previous_month' => 'Mois precedent',
                'year' => 'Cette annee',
            ],
            'statuses' => [
                null => 'Tous',
                'active' => 'Actifs',
                'new' => 'Nouveaux',
                'lost' => 'Perdus',
            ],
        ];
    }
}
