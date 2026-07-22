<?php

namespace Modules\CrmStats\Services;

use App\Models\CachedBillingStat;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use stdClass;

class BillingStatsDashboardService
{
    /**
     * @return array{start:CarbonImmutable,end:CarbonImmutable,label:string,preset:string}
     */
    public function period(string $preset = 'month', ?string $from = null, ?string $to = null): array
    {
        $today = CarbonImmutable::today();

        if ($preset === 'custom' && $from && $to) {
            return [
                'start' => CarbonImmutable::parse($from)->startOfDay(),
                'end' => CarbonImmutable::parse($to)->endOfDay(),
                'label' => 'Periode personnalisee',
                'preset' => 'custom',
            ];
        }

        return match ($preset) {
            'today' => [
                'start' => $today->startOfDay(),
                'end' => $today->endOfDay(),
                'label' => 'Aujourd hui',
                'preset' => 'today',
            ],
            'year' => [
                'start' => $today->startOfYear(),
                'end' => $today->endOfYear(),
                'label' => 'Cette annee',
                'preset' => 'year',
            ],
            'previous_month' => [
                'start' => $today->subMonthNoOverflow()->startOfMonth(),
                'end' => $today->subMonthNoOverflow()->endOfMonth(),
                'label' => 'Mois precedent',
                'preset' => 'previous_month',
            ],
            default => [
                'start' => $today->startOfMonth(),
                'end' => $today->endOfMonth(),
                'label' => 'Ce mois',
                'preset' => 'month',
            ],
        };
    }

    /**
     * @return array{total_revenue:float,invoice_count:int,new_clients:int,lost_clients:int,average_basket:float,conversion_rate:float,last_synced_at:?string}
     */
    public function summary(CarbonInterface $start, CarbonInterface $end): array
    {
        return Cache::remember($this->cacheKey('summary', $start, $end), $this->ttl(), function () use ($start, $end): array {
            $query = $this->baseQuery($start, $end);
            $totalRevenue = (float) (clone $query)->sum('total_amount');
            $invoiceCount = (int) (clone $query)->sum('invoice_count');
            $lostThreshold = CarbonImmutable::instance($end)->subMonths((int) config('crm-stats.lost_client_months', 6))->toDateString();
            $lastSyncedAt = (clone $query)->max('updated_at');

            return [
                'total_revenue' => round($totalRevenue, 2),
                'invoice_count' => $invoiceCount,
                'new_clients' => (int) (clone $query)
                    ->where('client_status', 'new')
                    ->distinct('client_id')
                    ->count('client_id'),
                'lost_clients' => (int) (clone $query)
                    ->where(function (Builder $builder) use ($lostThreshold): void {
                        $builder
                            ->where('client_status', 'lost')
                            ->orWhere('last_invoice_date', '<=', $lostThreshold);
                    })
                    ->distinct('client_id')
                    ->count('client_id'),
                'average_basket' => $invoiceCount > 0 ? round($totalRevenue / $invoiceCount, 2) : 0.0,
                'conversion_rate' => 0.0,
                'last_synced_at' => is_string($lastSyncedAt) ? $lastSyncedAt : null,
            ];
        });
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function revenueSeries(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $this->baseQuery($start, $end)
            ->selectRaw('date as period, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->toBase()
            ->get();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function familyBreakdown(CarbonInterface $start, CarbonInterface $end, int $limit = 8): Collection
    {
        return $this->baseQuery($start, $end)
            ->selectRaw('product_family, SUM(total_amount) as total')
            ->groupBy('product_family')
            ->orderByDesc('total')
            ->limit($limit)
            ->toBase()
            ->get();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function clientRows(CarbonInterface $start, CarbonInterface $end, ?string $status = null, int $limit = 50): Collection
    {
        return $this->baseQuery($start, $end)
            ->when($status, fn (Builder $query): Builder => $query->where('client_status', $status))
            ->selectRaw('client_id, client_name, client_status, SUM(total_amount) as total, SUM(invoice_count) as invoice_count, MAX(last_invoice_date) as last_invoice_date')
            ->groupBy('client_id', 'client_name', 'client_status')
            ->orderByDesc('total')
            ->limit($limit)
            ->toBase()
            ->get()
            ->map(function (object $row): object {
                $row->average_basket = (int) $row->invoice_count > 0 ? round((float) $row->total / (int) $row->invoice_count, 2) : 0.0;

                return $row;
            });
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function familyRows(CarbonInterface $start, CarbonInterface $end, int $limit = 50): Collection
    {
        $previousStart = CarbonImmutable::instance($start)->subDays($start->diffInDays($end) + 1);
        $previousEnd = CarbonImmutable::instance($start)->subDay();
        $previousTotals = $this->familyBreakdown($previousStart, $previousEnd, 100)
            ->keyBy('product_family')
            ->map(fn (object $row): float => (float) $row->total);

        return $this->baseQuery($start, $end)
            ->selectRaw('product_family, SUM(total_amount) as total, SUM(invoice_count) as invoice_count, SUM(quantity) as quantity')
            ->groupBy('product_family')
            ->orderByDesc('total')
            ->limit($limit)
            ->toBase()
            ->get()
            ->map(function (object $row) use ($previousTotals): object {
                $previousTotal = (float) ($previousTotals->get($row->product_family) ?? 0.0);
                $row->previous_total = $previousTotal;
                $row->evolution = $previousTotal > 0 ? round(((float) $row->total - $previousTotal) / $previousTotal * 100, 1) : 0.0;

                return $row;
            });
    }

    /**
     * @return array{left:array{label:string,value:float},right:array{label:string,value:float},delta:float}
     */
    public function compare(string $leftMetric, string $rightMetric, CarbonInterface $start, CarbonInterface $end): array
    {
        $summary = $this->summary($start, $end);
        $leftValue = (float) ($summary[$leftMetric] ?? 0);
        $rightValue = (float) ($summary[$rightMetric] ?? 0);

        return [
            'left' => ['label' => $this->metricLabel($leftMetric), 'value' => $leftValue],
            'right' => ['label' => $this->metricLabel($rightMetric), 'value' => $rightValue],
            'delta' => $rightValue > 0 ? round(($leftValue - $rightValue) / $rightValue * 100, 1) : 0.0,
        ];
    }

    public function clearCache(): void
    {
        Cache::increment('crm-stats:cache-version');
    }

    public function lastApiError(): ?array
    {
        $error = Cache::get('crm-stats:last-api-error');

        return is_array($error) ? $error : null;
    }

    private function baseQuery(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return CachedBillingStat::query()
            ->betweenDates($start->toDateString(), $end->toDateString());
    }

    private function cacheKey(string $scope, CarbonInterface $start, CarbonInterface $end): string
    {
        return implode(':', [
            'crm-stats',
            (string) Cache::get('crm-stats:cache-version', 1),
            $scope,
            $start->toDateString(),
            $end->toDateString(),
        ]);
    }

    private function ttl(): int
    {
        return max(60, (int) config('crm-stats.cache_ttl', 900));
    }

    private function metricLabel(string $metric): string
    {
        return [
            'total_revenue' => 'Chiffre d affaires',
            'invoice_count' => 'Nombre de factures',
            'new_clients' => 'Nouveaux clients',
            'lost_clients' => 'Clients perdus',
            'average_basket' => 'Panier moyen',
            'conversion_rate' => 'Taux de conversion',
        ][$metric] ?? $metric;
    }
}
