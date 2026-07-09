<?php

namespace App\Services\Crm;

use App\Models\CrmLeaveEmployee;
use App\Models\CrmLeaveEntry;
use App\Models\CrmModule;
use App\Models\CrmUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LeaveService
{
    private const TYPES = ['conge', 'rtt', 'absence', 'formation', 'maladie'];

    private const PERIODS = ['full', 'morning', 'afternoon'];

    private const STATUSES = ['approved', 'planned', 'pending', 'refused'];

    public function actorForUser(User $user): CrmUser
    {
        $actor = CrmUser::query()
            ->with(['modules:id,slug,active', 'permissions:id,name,label'])
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (! $actor) {
            $this->fail('Utilisateur CRM introuvable', 404);
        }

        return $actor;
    }

    public function bootstrap(CrmUser $actor): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.view');

        $employees = CrmLeaveEmployee::query()
            ->orderByDesc('active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $leaves = CrmLeaveEntry::query()
            ->with('employee')
            ->join('crm_leave_employees as employees', 'employees.id', '=', 'crm_leave_entries.employee_id')
            ->select('crm_leave_entries.*')
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
            ],
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

    public function saveEmployee(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.manage');

        return DB::transaction(function () use ($data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                $this->fail('Nom salarie requis', 400);
            }

            $employee = $id > 0
                ? CrmLeaveEmployee::query()->lockForUpdate()->find($id)
                : new CrmLeaveEmployee;

            if ($id > 0 && ! $employee) {
                $this->fail('Salarie introuvable', 404);
            }

            $employee->fill([
                'crm_user_id' => $this->optionalPositiveInt($data['crmUserId'] ?? $data['crm_user_id'] ?? null),
                'name' => $name,
                'slug' => $this->uniqueEmployeeSlug((string) ($data['slug'] ?? $name), $id ?: null),
                'color' => $this->color((string) ($data['color'] ?? '#f59e0b')),
                'active' => $this->boolean($data['active'] ?? null, true),
                'sort_order' => max(0, min(999, (int) ($data['sortOrder'] ?? $data['sort_order'] ?? 100))),
            ]);
            $employee->save();

            return ['ok' => true, 'employee' => $this->employeeRow($employee->refresh())];
        });
    }

    public function deleteEmployee(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.manage');

        return DB::transaction(function () use ($data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Salarie requis', 400);
            }

            $employee = CrmLeaveEmployee::query()->lockForUpdate()->find($id);

            if (! $employee) {
                $this->fail('Salarie introuvable', 404);
            }

            if ($employee->entries()->exists()) {
                $employee->forceFill(['active' => false])->save();

                return ['ok' => true, 'archived' => true];
            }

            $employee->delete();

            return ['ok' => true, 'deleted' => true];
        });
    }

    public function saveLeave(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'conges.manage');

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $employeeId = (int) ($data['employeeId'] ?? $data['employee_id'] ?? 0);

            if ($employeeId <= 0) {
                $this->fail('Salarie requis', 400);
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
                $this->fail('Salarie introuvable', 404);
            }

            $entry = $id > 0
                ? CrmLeaveEntry::query()->lockForUpdate()->find($id)
                : new CrmLeaveEntry;

            if ($id > 0 && ! $entry) {
                $this->fail('Conge introuvable', 404);
            }

            $conflictExists = CrmLeaveEntry::query()
                ->where('employee_id', $employeeId)
                ->when($id > 0, fn ($query) => $query->whereKeyNot($id))
                ->where('end_date', '>=', $startDate)
                ->where('start_date', '<=', $endDate)
                ->lockForUpdate()
                ->exists();

            if ($conflictExists) {
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

        return DB::transaction(function () use ($data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Conge requis', 400);
            }

            $entry = CrmLeaveEntry::query()->lockForUpdate()->find($id);

            if (! $entry) {
                $this->fail('Conge introuvable', 404);
            }

            $entry->delete();

            return ['ok' => true, 'deleted' => true];
        });
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
        $base = Str::slug($value) ?: 'salarie';
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

    private function boolean(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
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
