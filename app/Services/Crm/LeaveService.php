<?php

namespace App\Services\Crm;

use App\Models\CrmLeaveEmployee;
use App\Models\CrmLeaveEntry;
use App\Models\CrmModule;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use App\Queries\Crm\ReservationConflictQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LeaveService
{
    private const TYPES = ['conge', 'rtt', 'absence', 'formation', 'maladie'];

    private const PERIODS = ['full', 'morning', 'afternoon'];

    private const STATUSES = ['approved', 'planned', 'pending', 'refused'];

    public function __construct(private readonly ReservationConflictQuery $conflicts) {}

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

    public function bootstrap(CrmUser $actor, ?int $siteId = null): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.view');

        $selectedSiteId = $this->resolveSiteId($actor, $siteId);
        $sites = $this->availableSites($actor);
        $employees = $this->syncEmployeesForSite($selectedSiteId);
        $employeeIds = $employees->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $leaves = CrmLeaveEntry::query()
            ->with('employee')
            ->join('crm_leave_employees as employees', 'employees.id', '=', 'crm_leave_entries.employee_id')
            ->select('crm_leave_entries.*')
            ->whereIn('crm_leave_entries.employee_id', $employeeIds)
            ->orderBy('crm_leave_entries.start_date')
            ->orderBy('crm_leave_entries.end_date')
            ->orderBy('employees.sort_order')
            ->orderBy('employees.name')
            ->get();

        return [
            'ok' => true,
            'user' => [
                'id' => $actor->id,
                'name' => $actor->name,
                'role' => $actor->role,
                'permissions' => $actor->permissions->pluck('name')->values()->all(),
                'canManage' => $this->hasPermission($actor, 'conges.manage'),
                'siteIds' => $this->siteIds($actor),
                'selectedSiteId' => $selectedSiteId,
            ],
            'sites' => $sites->map(fn (CrmSite $site): array => $this->siteRow($site))->values()->all(),
            'selectedSiteId' => $selectedSiteId,
            'employees' => $employees->map(fn (CrmLeaveEmployee $employee): array => $this->employeeRow($employee))->values()->all(),
            'leaves' => $leaves->map(fn (CrmLeaveEntry $entry): array => $this->entryRow($entry))->values()->all(),
            'types' => [
                ['value' => 'conge', 'label' => 'Conge', 'color' => '#facc15'],
                ['value' => 'rtt', 'label' => 'RTT', 'color' => '#38bdf8'],
                ['value' => 'absence', 'label' => 'Absence', 'color' => '#fb7185'],
                ['value' => 'formation', 'label' => 'Formation', 'color' => '#a78bfa'],
                ['value' => 'maladie', 'label' => 'Maladie', 'color' => '#94a3b8'],
            ],
            'periods' => [
                ['value' => 'full', 'label' => 'Journee'],
                ['value' => 'morning', 'label' => 'Matin'],
                ['value' => 'afternoon', 'label' => 'Apres-midi'],
            ],
        ];
    }

    public function saveLeave(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.manage');

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $employeeId = (int) ($data['employeeId'] ?? $data['employee_id'] ?? 0);
            $siteId = $this->optionalPositiveInt($data['siteId'] ?? $data['site_id'] ?? null);

            if ($employeeId <= 0) {
                $this->fail('Utilisateur CRM requis', 400);
            }

            $startDate = $this->date((string) ($data['startDate'] ?? $data['start_date'] ?? ''), 'debut');
            $endDate = $this->date((string) ($data['endDate'] ?? $data['end_date'] ?? $startDate), 'fin');

            if ($endDate < $startDate) {
                $this->fail('La date de fin doit etre apres le debut', 400);
            }

            if (CarbonImmutable::parse($startDate)->diffInDays(CarbonImmutable::parse($endDate)) > 370) {
                $this->fail('Periode trop longue', 400);
            }

            $employee = CrmLeaveEmployee::query()
                ->where('active', true)
                ->lockForUpdate()
                ->find($employeeId);

            if (! $employee) {
                $this->fail('Utilisateur CRM introuvable', 404);
            }

            $this->requireEmployeeSiteAccess($actor, $employee, $siteId);

            $entry = $id > 0
                ? CrmLeaveEntry::query()->lockForUpdate()->find($id)
                : new CrmLeaveEntry;

            if ($id > 0 && ! $entry) {
                $this->fail('Conge introuvable', 404);
            }

            if ($entry->exists) {
                $currentEmployee = CrmLeaveEmployee::query()
                    ->lockForUpdate()
                    ->find((int) $entry->employee_id);

                if (! $currentEmployee) {
                    $this->fail('Utilisateur CRM introuvable', 404);
                }

                $this->requireEmployeeSiteAccess($actor, $currentEmployee, $siteId);
            }

            if ($this->conflicts->leaveOverlaps($employeeId, $startDate, $endDate, $id > 0 ? $id : null)) {
                $this->fail('Un conge existe deja sur cette periode', 409);
            }

            $entry->fill([
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $this->choice((string) ($data['type'] ?? 'conge'), self::TYPES, 'conge'),
                'period' => $this->choice((string) ($data['period'] ?? 'full'), self::PERIODS, 'full'),
                'status' => $this->choice((string) ($data['status'] ?? 'approved'), self::STATUSES, 'approved'),
                'notes' => trim((string) ($data['notes'] ?? '')),
                'source' => $entry->exists ? $entry->source : 'crm',
                'created_by' => $entry->exists ? $entry->created_by : $actor->id,
                'updated_by' => $actor->id,
            ]);
            $entry->save();

            return ['ok' => true, 'leave' => $this->entryRow($entry->refresh()->load('employee'))];
        });
    }

    public function deleteLeave(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.manage');

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);
            $siteId = $this->optionalPositiveInt($data['siteId'] ?? $data['site_id'] ?? null);

            if ($id <= 0) {
                $this->fail('Conge requis', 400);
            }

            $entry = CrmLeaveEntry::query()->lockForUpdate()->find($id);

            if (! $entry) {
                $this->fail('Conge introuvable', 404);
            }

            $employee = CrmLeaveEmployee::query()->lockForUpdate()->find((int) $entry->employee_id);
            if (! $employee) {
                $this->fail('Utilisateur CRM introuvable', 404);
            }

            $this->requireEmployeeSiteAccess($actor, $employee, $siteId);

            $entry->delete();

            return ['ok' => true, 'deleted' => true];
        });
    }

    private function syncEmployeesForSite(int $siteId)
    {
        $users = CrmUser::query()
            ->where('active', true)
            ->whereHas('sites', fn ($query) => $query->where('crm_sites.id', $siteId))
            ->orderBy('name')
            ->get();

        return DB::transaction(function () use ($users) {
            return $users->values()->map(function (CrmUser $user, int $index): CrmLeaveEmployee {
                return $this->syncEmployeeForUser($user, ($index + 1) * 10);
            });
        });
    }

    private function syncEmployeeForUser(CrmUser $user, int $sortOrder): CrmLeaveEmployee
    {
        $employee = CrmLeaveEmployee::query()
            ->where('crm_user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if (! $employee) {
            $employee = $this->legacyEmployeeForUser($user) ?? new CrmLeaveEmployee;
        }

        $displayName = $this->displayNameForUser($user);

        $employee->fill([
            'crm_user_id' => $user->id,
            'name' => $displayName,
            'slug' => $employee->exists
                ? $employee->slug
                : $this->uniqueEmployeeSlug($this->slugCandidateForUser($user, $displayName)),
            'color' => $employee->color ?: $this->colorForUser($user),
            'active' => true,
            'sort_order' => $employee->exists ? (int) $employee->sort_order : $sortOrder,
        ]);
        $employee->save();

        return $employee->refresh();
    }

    private function legacyEmployeeForUser(CrmUser $user): ?CrmLeaveEmployee
    {
        $slugs = collect([
            $user->name,
            $user->first_name,
            trim((string) $user->first_name.' '.(string) $user->last_name),
        ])
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->flatMap(function ($value): array {
                $slug = Str::slug((string) $value);
                $firstPart = Str::slug(strtok((string) $value, ' ') ?: (string) $value);

                return [$slug, $firstPart];
            })
            ->flatMap(function (string $slug): array {
                $aliases = [
                    'christophe-l' => 'christophe',
                    'j-philippe' => 'jean-philippe',
                    'jean-philippe' => 'jean-philippe',
                    'jeremy-l' => 'jeremy',
                    'philippe-p' => 'philippe',
                    'remi-g' => 'remi',
                    'samy' => 'sami',
                    'samy-i' => 'sami',
                ];

                return [$slug, $aliases[$slug] ?? $slug];
            })
            ->filter()
            ->unique()
            ->values();

        if ($slugs->isEmpty()) {
            return null;
        }

        return CrmLeaveEmployee::query()
            ->whereIn('slug', $slugs->all())
            ->where(function ($query) use ($user): void {
                $query->whereNull('crm_user_id')
                    ->orWhere('crm_user_id', $user->id);
            })
            ->orderByRaw('crm_user_id IS NULL')
            ->lockForUpdate()
            ->first();
    }

    private function requireEmployeeSiteAccess(CrmUser $actor, CrmLeaveEmployee $employee, ?int $siteId): void
    {
        $selectedSiteId = $this->resolveSiteId($actor, $siteId);
        $crmUserId = (int) $employee->crm_user_id;

        if ($crmUserId <= 0) {
            $this->fail('Utilisateur non lie a un compte CRM', 403);
        }

        $exists = CrmUser::query()
            ->whereKey($crmUserId)
            ->where('active', true)
            ->whereHas('sites', fn ($query) => $query->where('crm_sites.id', $selectedSiteId))
            ->exists();

        if (! $exists) {
            $this->fail('Utilisateur non autorise sur ce site', 403);
        }
    }

    private function resolveSiteId(CrmUser $actor, ?int $siteId): int
    {
        $siteIds = $this->siteIds($actor);

        if ($siteIds === []) {
            $this->fail('Aucun site autorise', 403);
        }

        $selectedSiteId = $siteId && $siteId > 0 ? $siteId : $siteIds[0];

        if (! in_array($selectedSiteId, $siteIds, true)) {
            $this->fail('Site non autorise', 403);
        }

        $siteExists = CrmSite::query()
            ->active()
            ->whereKey($selectedSiteId)
            ->exists();

        if (! $siteExists) {
            $this->fail('Site introuvable', 404);
        }

        return $selectedSiteId;
    }

    private function availableSites(CrmUser $actor)
    {
        return CrmSite::query()
            ->active()
            ->whereIn('id', $this->siteIds($actor))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function siteIds(CrmUser $user): array
    {
        return $user->sites()
            ->whereNull('crm_sites.deleted_at')
            ->orderByDesc('crm_user_sites.is_default')
            ->orderBy('crm_sites.id')
            ->pluck('crm_sites.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function requireModule(CrmUser $actor): void
    {
        $actor->loadMissing(['modules:id,slug,active', 'permissions:id,name,label']);

        $module = CrmModule::query()
            ->where('slug', 'conges')
            ->where('active', true)
            ->first();

        if (! $module || ! $actor->modules->contains('id', $module->id)) {
            $this->fail('Module non autorise : conges', 403);
        }
    }

    private function requirePermission(CrmUser $actor, string $permission): void
    {
        if (! $this->hasPermission($actor, $permission)) {
            $this->fail('Droit insuffisant : '.$permission, 403);
        }
    }

    private function hasPermission(CrmUser $actor, string $permission): bool
    {
        $actor->loadMissing('permissions:id,name,label');

        return $actor->permissions->contains('name', $permission);
    }

    private function uniqueEmployeeSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'utilisateur';
        $slug = $base;
        $suffix = 2;

        while (CrmLeaveEmployee::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function displayNameForUser(CrmUser $user): string
    {
        $name = trim((string) $user->name);

        if ($name !== '') {
            return $name;
        }

        $name = trim((string) $user->first_name.' '.(string) $user->last_name);

        return $name !== '' ? $name : 'Utilisateur #'.$user->id;
    }

    private function slugCandidateForUser(CrmUser $user, string $displayName): string
    {
        $name = trim((string) $user->name);

        if ($name !== '') {
            return $name;
        }

        return $displayName;
    }

    private function colorForUser(CrmUser $user): string
    {
        $palette = [
            '#2563eb',
            '#16a34a',
            '#64748b',
            '#dc2626',
            '#9333ea',
            '#f59e0b',
            '#0891b2',
            '#be123c',
        ];

        return $palette[max(0, ((int) $user->id - 1) % count($palette))];
    }

    private function siteRow(CrmSite $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'slug' => $site->slug,
            'active' => (bool) $site->active,
        ];
    }

    private function employeeRow(CrmLeaveEmployee $employee): array
    {
        return [
            'id' => $employee->id,
            'crmUserId' => $employee->crm_user_id,
            'name' => $employee->name,
            'slug' => $employee->slug,
            'color' => $employee->color ?: '#f59e0b',
            'active' => (bool) $employee->active,
            'sortOrder' => (int) $employee->sort_order,
        ];
    }

    private function entryRow(CrmLeaveEntry $entry): array
    {
        $employee = $entry->relationLoaded('employee') ? $entry->employee : $entry->employee()->first();

        return [
            'id' => $entry->id,
            'employeeId' => $entry->employee_id,
            'employeeName' => $employee?->name ?? '',
            'employeeColor' => $employee?->color ?? '#f59e0b',
            'startDate' => $entry->start_date?->toDateString(),
            'endDate' => $entry->end_date?->toDateString(),
            'type' => $entry->type ?: 'conge',
            'period' => $entry->period ?: 'full',
            'status' => $entry->status ?: 'approved',
            'notes' => $entry->notes ?? '',
            'source' => $entry->source ?? 'crm',
        ];
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function date(string $value, string $field): string
    {
        $value = trim($value);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->fail('Date invalide : '.$field, 400);
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        if (! checkdate($month, $day, $year)) {
            $this->fail('Date invalide : '.$field, 400);
        }

        return $value;
    }

    private function color(string $value): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', trim($value))
            ? strtolower(trim($value))
            : '#f59e0b';
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function choice(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
