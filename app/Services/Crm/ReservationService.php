<?php

namespace App\Services\Crm;

use App\Models\CrmMenuItem;
use App\Models\CrmModule;
use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use App\Queries\Crm\ReservationConflictQuery;
use App\Support\CrmReferenceCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ReservationService
{
    public function __construct(
        private readonly ReservationConflictQuery $conflicts,
        private readonly CrmActivityLogger $activity,
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

    public function bootstrap(CrmUser $actor): array
    {
        $this->requireModule($actor, 'reservations');

        $allowedSiteIds = $this->siteIds($actor);
        if ($allowedSiteIds === []) {
            $this->fail('Aucun site autorise pour les reservations', 403);
        }

        $reservations = CrmReservation::query()
            ->with(['site:id,name', 'user:id,name', 'vehicle:id,name'])
            ->whereIn('site_id', $allowedSiteIds)
            ->orderBy('start_at')
            ->orderBy('id')
            ->get();

        $users = CrmUser::query()
            ->with(['modules:id', 'permissions:id,name,sort_order', 'sites:id'])
            ->where('active', true)
            ->orderBy('id')
            ->get();

        return [
            'ok' => true,
            'mode' => 'mysql',
            'user' => $this->actorRow($actor),
            'sites' => collect($this->activeSiteRows())->whereIn('id', $allowedSiteIds)->values()->all(),
            'modules' => $this->activeModuleRows(),
            'vehicles' => collect($this->activeVehicleRows())->whereIn('siteId', $allowedSiteIds)->values()->all(),
            'reservations' => $reservations->map(fn (CrmReservation $reservation): array => $this->reservationRow($reservation))->values()->all(),
            'permissions' => $this->permissionRows(),
            'users' => $users->map(fn (CrmUser $user): array => $this->userRow($user))->values()->all(),
        ];
    }

    public function createReservation(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor, 'reservations');

        return DB::transaction(function () use ($actor, $data): array {
            $vehicleId = (int) ($data['vehicleId'] ?? $data['vehicle_id'] ?? 0);
            $startAt = $this->normalizeDateTime((string) ($data['startAt'] ?? $data['start_at'] ?? ''));
            $endAt = $this->normalizeDateTime((string) ($data['endAt'] ?? $data['end_at'] ?? ''));
            $title = trim((string) ($data['title'] ?? ''));
            $contactPhone = trim((string) ($data['contactPhone'] ?? $data['contact_phone'] ?? ''));
            $notes = trim((string) ($data['notes'] ?? ''));

            $this->validateReservationPayload($vehicleId, $startAt, $endAt, $title, $contactPhone);

            $vehicle = CrmVehicle::query()
                ->active()
                ->lockForUpdate()
                ->find($vehicleId);

            if (! $vehicle) {
                $this->fail('Vehicule introuvable', 404);
            }

            $site = CrmSite::query()->find((int) $vehicle->site_id);

            if (! $site) {
                $this->fail('Site introuvable', 404);
            }

            $this->requireSitePermission($actor, (int) $site->id, 'reservations.create');
            $this->requireWithinSiteHours($site, $startAt, $endAt);
            $this->requireNoReservationConflict($vehicleId, $startAt, $endAt);

            $reservation = CrmReservation::query()->create([
                'site_id' => $site->id,
                'vehicle_id' => $vehicleId,
                'user_id' => $actor->id,
                'user_name' => $actor->name,
                'title' => $title,
                'contact_phone' => $contactPhone,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $notes,
            ]);

            $this->log($actor, 'creation reservation', "Reservation #{$reservation->id}");

            return ['ok' => true, 'reservation' => $this->reservationRow($reservation->refresh())];
        });
    }

    public function updateReservation(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor, 'reservations');

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);
            $vehicleId = (int) ($data['vehicleId'] ?? $data['vehicle_id'] ?? 0);
            $startAt = $this->normalizeDateTime((string) ($data['startAt'] ?? $data['start_at'] ?? ''));
            $endAt = $this->normalizeDateTime((string) ($data['endAt'] ?? $data['end_at'] ?? ''));
            $title = trim((string) ($data['title'] ?? ''));
            $contactPhone = trim((string) ($data['contactPhone'] ?? $data['contact_phone'] ?? ''));
            $notes = trim((string) ($data['notes'] ?? ''));

            if ($id <= 0) {
                $this->fail('Reservation invalide', 400);
            }

            $this->validateReservationPayload($vehicleId, $startAt, $endAt, $title, $contactPhone);

            $reservation = CrmReservation::query()
                ->lockForUpdate()
                ->find($id);

            if (! $reservation) {
                $this->fail('Reservation introuvable', 404);
            }

            $canUpdateAny = $this->canOnSite($actor, (int) $reservation->site_id, 'reservations.update_any');
            $canUpdateOwn = $this->canOnSite($actor, (int) $reservation->site_id, 'reservations.update_own') && (int) $reservation->user_id === (int) $actor->id;

            if (! $canUpdateAny && ! $canUpdateOwn) {
                $this->fail('Modification non autorisee', 403);
            }

            $vehicle = CrmVehicle::query()
                ->active()
                ->lockForUpdate()
                ->find($vehicleId);

            if (! $vehicle) {
                $this->fail('Vehicule introuvable', 404);
            }

            $site = CrmSite::query()->find((int) $vehicle->site_id);

            if (! $site) {
                $this->fail('Site introuvable', 404);
            }

            $canUpdateAnyOnNewSite = $this->canOnSite($actor, (int) $site->id, 'reservations.update_any');
            $canUpdateOwnOnNewSite = $this->canOnSite($actor, (int) $site->id, 'reservations.update_own') && (int) $reservation->user_id === (int) $actor->id;

            if (! $canUpdateAnyOnNewSite && ! $canUpdateOwnOnNewSite) {
                $this->fail('Site non autorise', 403);
            }

            $this->requireWithinSiteHours($site, $startAt, $endAt);
            $this->requireNoReservationConflict($vehicleId, $startAt, $endAt, $id);

            $reservation->fill([
                'site_id' => $site->id,
                'vehicle_id' => $vehicleId,
                'title' => $title,
                'contact_phone' => $contactPhone,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $notes,
            ]);
            $reservation->save();

            $this->log($actor, 'modification reservation', "Reservation #{$id}");

            return ['ok' => true, 'reservation' => $this->reservationRow($reservation->refresh())];
        });
    }

    public function saveVehicle(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor, 'reservations');

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $siteId = (int) ($data['siteId'] ?? $data['site_id'] ?? 0);
            $name = trim((string) ($data['name'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $color = $this->color((string) ($data['color'] ?? '#95002e'));
            $photoDataUrl = (string) ($data['photoDataUrl'] ?? $data['photo_data_url'] ?? '');

            $this->requireSitePermission($actor, $siteId, 'reservations.manage_vehicles');

            if ($name === '' || mb_strlen($name) > 160) {
                $this->fail('Nom de vehicule invalide', 400);
            }

            if (mb_strlen($description) > 255) {
                $this->fail('Description trop longue', 400);
            }

            $duplicateExists = CrmVehicle::query()
                ->where('name', $name)
                ->when($id > 0, fn ($query) => $query->whereKeyNot($id))
                ->lockForUpdate()
                ->exists();

            if ($duplicateExists) {
                $this->fail('Un vehicule porte deja ce nom', 409);
            }

            $vehicle = $id > 0
                ? CrmVehicle::query()->lockForUpdate()->find($id)
                : new CrmVehicle;

            if ($id > 0 && ! $vehicle) {
                $this->fail('Vehicule introuvable', 404);
            }

            if ($id > 0) {
                $this->requireSitePermission($actor, (int) $vehicle->site_id, 'reservations.manage_vehicles');
            }

            $photoUrl = $vehicle->getAttribute('photo_url') ?: null;

            if (trim($photoDataUrl) !== '') {
                $photoUrl = $this->saveDataImage($photoDataUrl, 'vehicles') ?: $photoUrl;
            }

            $vehicle->fill([
                'site_id' => $siteId,
                'name' => $name,
                'description' => $description,
                'color' => $color,
                'photo_url' => $photoUrl,
                'active' => true,
            ]);
            $vehicle->save();

            $this->log($actor, $id > 0 ? 'modification vehicule' : 'creation vehicule', "Vehicule #{$vehicle->id}");

            return ['ok' => true, 'vehicle' => $this->vehicleRow($vehicle->refresh())];
        });
    }

    public function deleteVehicle(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor, 'reservations');

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Vehicule invalide', 400);
            }

            $vehicle = CrmVehicle::query()
                ->lockForUpdate()
                ->find($id);

            if (! $vehicle) {
                $this->fail('Vehicule introuvable', 404);
            }

            $this->requireSitePermission($actor, (int) $vehicle->site_id, 'reservations.manage_vehicles');
            $vehicle->forceFill(['active' => false])->save();

            $this->log($actor, 'masquage vehicule', "Vehicule #{$id}");

            return ['ok' => true, 'id' => $id];
        });
    }

    public function deleteReservation(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor, 'reservations');

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Reservation invalide', 400);
            }

            $reservation = CrmReservation::query()
                ->lockForUpdate()
                ->find($id);

            if (! $reservation) {
                $this->fail('Reservation introuvable', 404);
            }

            $canDeleteAny = $this->canOnSite($actor, (int) $reservation->site_id, 'reservations.delete_any');
            $canDeleteOwn = $this->canOnSite($actor, (int) $reservation->site_id, 'reservations.delete_own') && (int) $reservation->user_id === (int) $actor->id;

            if (! $canDeleteAny && ! $canDeleteOwn) {
                $this->fail('Suppression non autorisee', 403);
            }

            $reservation->delete();
            $this->log($actor, 'suppression reservation', "Reservation #{$id}");

            return ['ok' => true];
        });
    }

    private function validateReservationPayload(int $vehicleId, string $startAt, string $endAt, string $title, string $contactPhone): void
    {
        if ($vehicleId <= 0 || strtotime($endAt) <= strtotime($startAt)) {
            $this->fail('Creneau invalide', 400);
        }

        $this->requireNotPastStartDate($startAt);

        if (mb_strlen($title) > 190) {
            $this->fail('Titre trop long', 400);
        }

        if (mb_strlen($contactPhone) > 40) {
            $this->fail('Telephone trop long', 400);
        }
    }

    private function requireNotPastStartDate(string $startAt): void
    {
        if (CarbonImmutable::parse($startAt)->startOfDay()->lt(CarbonImmutable::now()->startOfDay())) {
            $this->fail('Impossible de reserver dans le passe', 422);
        }
    }

    private function requireWithinSiteHours(CrmSite $site, string $startAt, string $endAt): void
    {
        if (! $site->containsOpeningPeriod($startAt, $endAt)) {
            $this->fail('Creneau hors horaires du site', 400);
        }
    }

    private function requireNoReservationConflict(int $vehicleId, string $startAt, string $endAt, ?int $ignoreId = null): void
    {
        if ($this->conflicts->vehicleOverlaps($vehicleId, $startAt, $endAt, $ignoreId)) {
            $this->fail('Le vehicule est deja reserve sur ce creneau', 409);
        }
    }

    private function requireModule(CrmUser $actor, string $slug): void
    {
        if (! $this->hasModule($actor, $slug)) {
            $this->fail('Module non autorise : '.$slug, 403);
        }
    }

    private function hasModule(CrmUser $actor, string $slug): bool
    {
        return $this->access->hasModule($actor, $slug);
    }

    private function requireSitePermission(CrmUser $actor, int $siteId, string $permission): void
    {
        if (! $this->canOnSite($actor, $siteId, $permission)) {
            $this->fail('Droit insuffisant : '.$permission, 403);
        }
    }

    private function canOnSite(CrmUser $actor, int $siteId, string $permission): bool
    {
        return $this->access->canOnSite($actor, $siteId, 'reservations', $permission);
    }

    private function actorRow(CrmUser $actor): array
    {
        return [
            'id' => $actor->id,
            'name' => $actor->name,
            'role' => $actor->role,
            'active' => (bool) $actor->active,
            'siteIds' => $this->siteIds($actor),
            'moduleIds' => $actor->role === 'blocked' ? [] : $this->access->moduleIds($actor),
            'permissions' => $actor->role === 'blocked' ? [] : $this->access->permissionNames($actor),
        ];
    }

    private function userRow(CrmUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'siteIds' => $this->siteIds($user),
            'moduleIds' => $this->access->moduleIds($user),
            'permissions' => $this->access->permissionNames($user),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function siteIds(CrmUser $user): array
    {
        return $this->access->siteIdsForModule($user, 'reservations', $this->reservationPermissionNames());
    }

    /**
     * @return array<int, string>
     */
    private function reservationPermissionNames(): array
    {
        return [
            'reservations.view',
            'reservations.create',
            'reservations.update_own',
            'reservations.update_any',
            'reservations.delete_own',
            'reservations.delete_any',
            'reservations.manage_vehicles',
        ];
    }

    private function siteRow(CrmSite $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'slug' => $site->slug,
            'hours' => [
                'morningStart' => $this->time5($site->morning_start, '07:30'),
                'morningEnd' => $this->time5($site->morning_end, '12:00'),
                'afternoonStart' => $this->time5($site->afternoon_start, '13:30'),
                'afternoonEnd' => $this->time5($site->afternoon_end, '17:30'),
            ],
        ];
    }

    private function moduleRow(CrmModule $module): array
    {
        return [
            'id' => $module->id,
            'name' => $module->name,
            'slug' => $module->slug,
            'description' => $module->description ?? '',
            'active' => (bool) $module->active,
            'sortOrder' => (int) $module->sort_order,
        ];
    }

    private function vehicleRow(CrmVehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'siteId' => (int) $vehicle->site_id,
            'name' => $vehicle->name,
            'description' => $vehicle->description ?? '',
            'color' => $vehicle->color ?: '#95002e',
            'photoUrl' => $vehicle->getAttribute('photo_url') ?? '',
            'active' => (bool) $vehicle->active,
        ];
    }

    private function reservationRow(CrmReservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'siteId' => (int) $reservation->site_id,
            'vehicleId' => (int) $reservation->vehicle_id,
            'userId' => (int) $reservation->user_id,
            'userName' => $reservation->user_name,
            'title' => $reservation->title ?? '',
            'contactPhone' => $reservation->contact_phone ?? '',
            'startAt' => $reservation->start_at?->format('Y-m-d\TH:i') ?? '',
            'endAt' => $reservation->end_at?->format('Y-m-d\TH:i') ?? '',
            'notes' => $reservation->notes ?? '',
        ];
    }

    private function activeSiteRows(): array
    {
        return Cache::rememberForever(CrmReferenceCache::ACTIVE_SITE_ROWS, function (): array {
            return CrmSite::query()
                ->active()
                ->orderBy('id')
                ->get()
                ->map(fn (CrmSite $site): array => $this->siteRow($site))
                ->values()
                ->all();
        });
    }

    private function activeModuleRows(): array
    {
        return Cache::rememberForever(CrmReferenceCache::ACTIVE_MODULE_ROWS, function (): array {
            $visibleModuleSlugs = CrmMenuItem::query()
                ->where('active', true)
                ->where('item_key', 'like', 'module:%')
                ->pluck('item_key')
                ->map(fn (string $key): string => substr($key, 7))
                ->filter()
                ->values()
                ->all();

            return CrmModule::query()
                ->active()
                ->whereIn('slug', $visibleModuleSlugs)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CrmModule $module): array => $this->moduleRow($module))
                ->values()
                ->all();
        });
    }

    private function activeVehicleRows(): array
    {
        return Cache::rememberForever(CrmReferenceCache::ACTIVE_VEHICLE_ROWS, function (): array {
            return CrmVehicle::query()
                ->active()
                ->orderBy('site_id')
                ->orderBy('name')
                ->get()
                ->map(fn (CrmVehicle $vehicle): array => $this->vehicleRow($vehicle))
                ->values()
                ->all();
        });
    }

    private function permissionRows(): array
    {
        return Cache::rememberForever(CrmReferenceCache::PERMISSION_ROWS, function (): array {
            return DB::table('crm_permissions')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (object $permission): array => [
                    'name' => $permission->name,
                    'label' => $permission->label,
                    'group' => $permission->group_label,
                ])
                ->values()
                ->all();
        });
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', trim($value));

        if ($value === '') {
            $this->fail('Date invalide', 400);
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (Throwable) {
            $this->fail('Date invalide', 400);
        }

        return $date->format('Y-m-d H:i:s');
    }

    private function saveDataImage(string $dataUrl, string $folder): ?string
    {
        $dataUrl = trim($dataUrl);

        if ($dataUrl === '') {
            return null;
        }

        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $dataUrl, $matches)) {
            $this->fail('Photo invalide', 400);
        }

        $binary = base64_decode(substr($dataUrl, (int) strpos($dataUrl, ',') + 1), true);

        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            $this->fail('Photo trop lourde', 400);
        }

        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $relativeDir = 'assets/uploads/'.$folder;
        $dir = public_path($relativeDir);

        if (! File::isDirectory($dir) && ! File::makeDirectory($dir, 0755, true, true) && ! File::isDirectory($dir)) {
            $this->fail('Impossible de stocker la photo', 500);
        }

        $file = now()->format('YmdHis').'-'.Str::random(10).'.'.$ext;
        $relativePath = $relativeDir.'/'.$file;

        if (File::put(public_path($relativePath), $binary) === false) {
            $this->fail('Impossible de stocker la photo', 500);
        }

        return '/'.str_replace('\\', '/', $relativePath);
    }

    private function color(string $value): string
    {
        $value = trim($value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '#95002e';
    }

    private function time5(?string $value, string $default): string
    {
        $value = trim((string) $value);

        return preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match) ? $match[1] : $default;
    }

    private function log(CrmUser $actor, string $action, string $details = ''): void
    {
        $this->activity->log($actor, $action, $details);
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
