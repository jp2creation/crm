<?php

namespace Modules\CrmCore\Services;

use App\Models\CrmCashReceipt;
use App\Models\CrmCashRegisterDay;
use App\Models\CrmCheckRemittance;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmLeaveEntry;
use App\Models\CrmModule;
use App\Models\CrmNotificationLog;
use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DashboardService
{
    public function __construct(
        private readonly CrmAccessService $access,
    ) {}

    public function actorForUser(User $user): CrmUser
    {
        $actor = CrmUser::query()
            ->with(['modules:id,slug,active', 'permissions:id,name,label', 'sites:id'])
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (! $actor) {
            $this->fail('Utilisateur CRM introuvable', 404);
        }

        return $actor;
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(CrmUser $actor, ?int $requestedSiteId = null): array
    {
        $generalSiteIds = $this->generalSiteIds($actor);
        $selectedSiteId = $this->selectedSiteId($actor, $requestedSiteId, $generalSiteIds);
        $cacheKey = sprintf('crm:dashboard:v1:user:%d:site:%s', $actor->id, $selectedSiteId ?: 'all');
        $ttl = max(0, (int) config('crm.dashboard.cache_seconds', 300));

        $payload = fn (): array => $this->buildOverview($actor, $selectedSiteId, $generalSiteIds);

        return $ttl > 0 ? Cache::remember($cacheKey, $ttl, $payload) : $payload();
    }

    /**
     * @param  array<int, int>  $generalSiteIds
     * @return array<string, mixed>
     */
    private function buildOverview(CrmUser $actor, ?int $selectedSiteId, array $generalSiteIds): array
    {
        $today = CarbonImmutable::today(config('crm.display_timezone', config('app.timezone', 'Europe/Paris')));
        $now = CarbonImmutable::now(config('crm.display_timezone', config('app.timezone', 'Europe/Paris')));
        $weekStart = $today->startOfWeek();
        $weekEnd = $today->endOfWeek();
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();

        $reservationSiteIds = $this->moduleSiteIds($actor, 'reservations', ['reservations.view'], $selectedSiteId);
        $equipmentSiteIds = $this->moduleSiteIds($actor, 'locations-materiel', ['equipment_rentals.view'], $selectedSiteId);
        $leaveSiteIds = $this->moduleSiteIds($actor, 'conges', ['conges.view'], $selectedSiteId);
        $cashSiteIds = $this->moduleSiteIds($actor, 'controle-caisse', ['controle_caisse.view'], $selectedSiteId);
        $checkSiteIds = $this->moduleSiteIds($actor, 'remise-cheques', ['check_remittances.view'], $selectedSiteId);

        $moduleAccess = [
            'reservations' => $reservationSiteIds !== [],
            'equipmentRentals' => $equipmentSiteIds !== [],
            'leaves' => $leaveSiteIds !== [],
            'cashControl' => $cashSiteIds !== [],
            'checkRemittances' => $checkSiteIds !== [],
        ];

        $reservationChart = $this->reservationChart($reservationSiteIds, $today);
        [$equipmentAvailable, $equipmentTotal] = $this->equipmentAvailability($equipmentSiteIds, $now);

        return [
            'ok' => true,
            'generatedAt' => $now->toIso8601String(),
            'cacheSeconds' => (int) config('crm.dashboard.cache_seconds', 300),
            'user' => [
                'id' => $actor->id,
                'name' => $actor->name,
                'role' => $actor->role,
                'siteIds' => $generalSiteIds,
            ],
            'selectedSiteId' => $selectedSiteId,
            'sites' => $this->siteRows($generalSiteIds),
            'modules' => $this->moduleRows($actor),
            'access' => $moduleAccess,
            'stats' => [
                'reservationsToday' => $this->reservationsToday($reservationSiteIds, $today),
                'monthlyRevenue' => $this->monthlyRevenue($cashSiteIds, $monthStart, $monthEnd),
                'pendingLeaves' => $this->pendingLeaves($leaveSiteIds),
                'equipmentAvailable' => $equipmentAvailable,
                'equipmentTotal' => $equipmentTotal,
            ],
            'reservationTrend' => $reservationChart,
            'latestReservations' => $this->latestReservations($reservationSiteIds),
            'currentLeaves' => $this->currentLeaves($leaveSiteIds, $weekStart, $weekEnd),
            'alerts' => $this->alerts($equipmentSiteIds, $cashSiteIds, $checkSiteIds, $now, $monthStart, $monthEnd),
            'notifications' => $this->notifications($leaveSiteIds, $checkSiteIds),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function generalSiteIds(CrmUser $actor): array
    {
        return $this->access->siteIds($actor);
    }

    /**
     * @param  array<int, int>  $generalSiteIds
     */
    private function selectedSiteId(CrmUser $actor, ?int $requestedSiteId, array $generalSiteIds): ?int
    {
        if ($requestedSiteId && in_array($requestedSiteId, $generalSiteIds, true)) {
            return $requestedSiteId;
        }

        $defaultSiteId = (int) DB::table('crm_user_sites')
            ->where('user_id', $actor->id)
            ->where('is_default', true)
            ->value('site_id');

        if ($defaultSiteId > 0 && in_array($defaultSiteId, $generalSiteIds, true)) {
            return $defaultSiteId;
        }

        return $generalSiteIds[0] ?? null;
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, int>
     */
    private function moduleSiteIds(CrmUser $actor, string $moduleSlug, array $permissions, ?int $selectedSiteId): array
    {
        $siteIds = $this->access->siteIdsForModule($actor, $moduleSlug, $permissions);

        if ($selectedSiteId) {
            return in_array($selectedSiteId, $siteIds, true) ? [$selectedSiteId] : [];
        }

        return $siteIds;
    }

    /**
     * @param  array<int, int>  $siteIds
     */
    private function reservationsToday(array $siteIds, CarbonImmutable $today): int
    {
        if ($siteIds === []) {
            return 0;
        }

        return CrmReservation::query()
            ->whereIn('site_id', $siteIds)
            ->where('start_at', '<=', $today->endOfDay())
            ->where('end_at', '>=', $today->startOfDay())
            ->count();
    }

    /**
     * @param  array<int, int>  $siteIds
     */
    private function monthlyRevenue(array $siteIds, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): float
    {
        if ($siteIds === []) {
            return 0.0;
        }

        return (float) CrmCashReceipt::query()
            ->whereBetween('occurred_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('day', fn (Builder $query): Builder => $query->whereIn('site_id', $siteIds))
            ->sum('invoice_total');
    }

    /**
     * @param  array<int, int>  $siteIds
     */
    private function pendingLeaves(array $siteIds): int
    {
        if ($siteIds === []) {
            return 0;
        }

        return CrmLeaveEntry::query()
            ->where('status', 'pending')
            ->whereHas('employee.crmUser.sites', fn (Builder $query): Builder => $query->whereIn('crm_sites.id', $siteIds))
            ->count();
    }

    /**
     * @param  array<int, int>  $siteIds
     * @return array{0: int, 1: int}
     */
    private function equipmentAvailability(array $siteIds, CarbonImmutable $now): array
    {
        if ($siteIds === []) {
            return [0, 0];
        }

        $total = CrmEquipmentItem::query()
            ->active()
            ->whereIn('site_id', $siteIds)
            ->count();

        $busy = CrmEquipmentRental::query()
            ->whereIn('site_id', $siteIds)
            ->whereNotIn('status', [CrmEquipmentRental::STATUS_CANCELLED, CrmEquipmentRental::STATUS_RETURNED])
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->distinct('equipment_item_id')
            ->count('equipment_item_id');

        return [max(0, $total - $busy), $total];
    }

    /**
     * @param  array<int, int>  $siteIds
     * @return array<int, array{date: string, label: string, total: int}>
     */
    private function reservationChart(array $siteIds, CarbonImmutable $today): array
    {
        $start = $today->subDays(6)->startOfDay();
        $end = $today->endOfDay();
        $labels = collect(range(0, 6))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));

        if ($siteIds === []) {
            return $labels
                ->map(fn (CarbonImmutable $date): array => [
                    'date' => $date->toDateString(),
                    'label' => $date->translatedFormat('d/m'),
                    'total' => 0,
                ])
                ->all();
        }

        $rows = CrmReservation::query()
            ->selectRaw('DATE(start_at) as day, COUNT(*) as total')
            ->whereIn('site_id', $siteIds)
            ->whereBetween('start_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        return $labels
            ->map(fn (CarbonImmutable $date): array => [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('d/m'),
                'total' => (int) ($rows[$date->toDateString()] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $siteIds
     * @return array<int, array<string, mixed>>
     */
    private function latestReservations(array $siteIds): array
    {
        if ($siteIds === []) {
            return [];
        }

        return CrmReservation::query()
            ->with(['site:id,name', 'vehicle:id,name', 'user:id,name'])
            ->whereIn('site_id', $siteIds)
            ->latest('start_at')
            ->limit(5)
            ->get()
            ->map(fn (CrmReservation $reservation): array => [
                'id' => $reservation->id,
                'title' => $reservation->title ?: 'Réservation véhicule',
                'site' => $reservation->site?->name ?? '',
                'vehicle' => $reservation->vehicle?->name ?? '',
                'user' => $reservation->user?->name ?? $reservation->user_name,
                'startAt' => $reservation->start_at?->format('Y-m-d\TH:i'),
                'endAt' => $reservation->end_at?->format('Y-m-d\TH:i'),
                'status' => $reservation->end_at && $reservation->end_at->isPast() ? 'passée' : 'prévue',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $siteIds
     * @return array<int, array<string, mixed>>
     */
    private function currentLeaves(array $siteIds, CarbonImmutable $weekStart, CarbonImmutable $weekEnd): array
    {
        if ($siteIds === []) {
            return [];
        }

        return CrmLeaveEntry::query()
            ->with(['employee.crmUser.sites'])
            ->where('status', '<>', 'refused')
            ->whereDate('start_date', '<=', $weekEnd->toDateString())
            ->whereDate('end_date', '>=', $weekStart->toDateString())
            ->whereHas('employee.crmUser.sites', fn (Builder $query): Builder => $query->whereIn('crm_sites.id', $siteIds))
            ->orderBy('start_date')
            ->limit(8)
            ->get()
            ->map(fn (CrmLeaveEntry $leave): array => [
                'id' => $leave->id,
                'name' => $leave->employee?->name ?? 'Utilisateur',
                'color' => $leave->employee?->color ?? '#95002e',
                'type' => $this->leaveTypeLabel((string) $leave->type),
                'period' => $this->leavePeriodLabel((string) $leave->period),
                'status' => $this->leaveStatusLabel((string) $leave->status),
                'startDate' => $leave->start_date?->toDateString(),
                'endDate' => $leave->end_date?->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $equipmentSiteIds
     * @param  array<int, int>  $cashSiteIds
     * @param  array<int, int>  $checkSiteIds
     * @return array<int, array<string, mixed>>
     */
    private function alerts(array $equipmentSiteIds, array $cashSiteIds, array $checkSiteIds, CarbonImmutable $now, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): array
    {
        $alerts = [];

        if ($equipmentSiteIds !== []) {
            $lateReturns = CrmEquipmentRental::query()
                ->whereIn('site_id', $equipmentSiteIds)
                ->whereIn('status', [CrmEquipmentRental::STATUS_RESERVED, CrmEquipmentRental::STATUS_PICKED_UP])
                ->where('end_at', '<', $now)
                ->count();

            if ($lateReturns > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'label' => 'Retours matériel',
                    'value' => $lateReturns,
                    'detail' => 'Location(s) à clôturer',
                    'href' => '/locations-materiel',
                ];
            }
        }

        if ($cashSiteIds !== []) {
            $cashReviews = CrmCashRegisterDay::query()
                ->whereIn('site_id', $cashSiteIds)
                ->whereBetween('cash_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->where(fn (Builder $query): Builder => $query
                    ->whereIn('status', [CrmCashRegisterDay::STATUS_ANOMALY, CrmCashRegisterDay::STATUS_REVIEW])
                    ->orWhere('invoice_errors_count', '>', 0))
                ->count();

            if ($cashReviews > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'label' => 'Caisses à vérifier',
                    'value' => $cashReviews,
                    'detail' => 'Jour(s) du mois en anomalie',
                    'href' => '/controle-caisse',
                ];
            }

            $invoiceDiffs = CrmCashReceipt::query()
                ->whereBetween('occurred_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereHas('day', fn (Builder $query): Builder => $query->whereIn('site_id', $cashSiteIds))
                ->whereRaw('ABS(invoice_total - (cash_amount + card_amount + check_amount + transfer_amount + control_amount)) > ?', [0.01])
                ->count();

            if ($invoiceDiffs > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'label' => 'Factures à vérifier',
                    'value' => $invoiceDiffs,
                    'detail' => 'Écart entre facture et paiement',
                    'href' => '/controle-caisse?view=list',
                ];
            }
        }

        if ($checkSiteIds !== []) {
            $draftRemittances = CrmCheckRemittance::query()
                ->whereIn('site_id', $checkSiteIds)
                ->where('status', CrmCheckRemittance::STATUS_DRAFT)
                ->count();

            if ($draftRemittances > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'label' => 'Remises en brouillon',
                    'value' => $draftRemittances,
                    'detail' => 'Remise(s) de chèques à finaliser',
                    'href' => '/remise-cheques',
                ];
            }
        }

        return $alerts;
    }

    /**
     * @param  array<int, int>  $leaveSiteIds
     * @param  array<int, int>  $checkSiteIds
     * @return array<string, mixed>
     */
    private function notifications(array $leaveSiteIds, array $checkSiteIds): array
    {
        $pendingLeaves = $this->pendingLeaves($leaveSiteIds);
        $draftChecks = $checkSiteIds === []
            ? 0
            : CrmCheckRemittance::query()->whereIn('site_id', $checkSiteIds)->where('status', CrmCheckRemittance::STATUS_DRAFT)->count();
        $failedNotifications = CrmNotificationLog::query()->failed()->count();

        return [
            'pendingLeaves' => $pendingLeaves,
            'draftCheckRemittances' => $draftChecks,
            'failedNotifications' => $failedNotifications,
            'total' => $pendingLeaves + $draftChecks + $failedNotifications,
        ];
    }

    /**
     * @param  array<int, int>  $siteIds
     * @return array<int, array{id: int, name: string}>
     */
    private function siteRows(array $siteIds): array
    {
        if ($siteIds === []) {
            return [];
        }

        return CrmSite::query()
            ->active()
            ->whereIn('id', $siteIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CrmSite $site): array => ['id' => (int) $site->id, 'name' => $site->name])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function moduleRows(CrmUser $actor): array
    {
        $moduleIds = $this->access->moduleIds($actor);

        if ($moduleIds === []) {
            return [];
        }

        return CrmModule::query()
            ->whereIn('id', $moduleIds)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'route_path'])
            ->map(fn (CrmModule $module): array => [
                'id' => (int) $module->id,
                'name' => $module->name,
                'slug' => $module->slug,
                'routePath' => $module->route_path,
            ])
            ->values()
            ->all();
    }

    private function leaveTypeLabel(string $type): string
    {
        return [
            'conge' => 'Congé',
            'rtt' => 'RTT',
            'absence' => 'Absence',
            'formation' => 'Formation',
            'maladie' => 'Maladie',
        ][$type] ?? ucfirst($type);
    }

    private function leavePeriodLabel(string $period): string
    {
        return [
            'full' => 'Journée',
            'morning' => 'Matin',
            'afternoon' => 'Après-midi',
        ][$period] ?? $period;
    }

    private function leaveStatusLabel(string $status): string
    {
        return [
            'approved' => 'Validé',
            'planned' => 'Planifié',
            'pending' => 'En attente',
            'refused' => 'Refusé',
        ][$status] ?? $status;
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
