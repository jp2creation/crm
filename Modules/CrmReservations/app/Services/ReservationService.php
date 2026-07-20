<?php

namespace Modules\CrmReservations\Services;

use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\CrmCore\Events\CrmDomainEvent;
use Modules\CrmCore\Queries\ReservationConflictQuery;
use Modules\CrmCore\Services\CrmAccessService;
use Modules\CrmCore\Services\CrmActivityLogger;
use Modules\CrmCore\Support\CrmReferenceCache;
use Modules\CrmReservations\Data\ReservationPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            ->select(['id', 'name'])
            ->where('active', true)
            ->whereHas('sites', fn ($query) => $query->whereIn('crm_sites.id', $allowedSiteIds))
            ->orderBy('name')
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
            $payload = ReservationPayload::fromArray($data);
            $this->requireNotPastStartDate($payload->startAt);

            $vehicle = CrmVehicle::query()
                ->active()
                ->lockForUpdate()
                ->find($payload->vehicleId);

            if (! $vehicle) {
                $this->fail('Vehicule introuvable', 404);
            }

            $site = CrmSite::query()->find((int) $vehicle->site_id);

            if (! $site) {
                $this->fail('Site introuvable', 404);
            }

            $this->requireSitePermission($actor, (int) $site->id, 'reservations.create');
            $this->requireWithinVehicleHours($vehicle, $site, $payload->startAt, $payload->endAt);
            $this->requireNoReservationConflict($payload->vehicleId, $payload->startAt, $payload->endAt);

            $reservation = CrmReservation::query()->create([
                'site_id' => $site->id,
                'vehicle_id' => $payload->vehicleId,
                'user_id' => $actor->id,
                'user_name' => $actor->name,
                'title' => $payload->title,
                'contact_phone' => $payload->contactPhone,
                'start_at' => $payload->startAt,
                'end_at' => $payload->endAt,
                'notes' => $payload->notes,
            ]);

            $this->dispatchReservationEvent($actor, 'created', $reservation->refresh());

            return ['ok' => true, 'reservation' => $this->reservationRow($reservation)];
        });
    }

    public function updateReservation(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor, 'reservations');

        return DB::transaction(function () use ($actor, $data): array {
            $payload = ReservationPayload::fromArray($data, true);
            $this->requireNotPastStartDate($payload->startAt);

            $reservation = CrmReservation::query()
                ->lockForUpdate()
                ->find($payload->id);

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
                ->find($payload->vehicleId);

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

            $this->requireWithinVehicleHours($vehicle, $site, $payload->startAt, $payload->endAt);
            $this->requireNoReservationConflict($payload->vehicleId, $payload->startAt, $payload->endAt, $payload->id);

            $reservation->fill([
                'site_id' => $site->id,
                'vehicle_id' => $payload->vehicleId,
                'title' => $payload->title,
                'contact_phone' => $payload->contactPhone,
                'start_at' => $payload->startAt,
                'end_at' => $payload->endAt,
                'notes' => $payload->notes,
            ]);
            $reservation->save();

            $this->dispatchReservationEvent($actor, 'updated', $reservation->refresh());

            return ['ok' => true, 'reservation' => $this->reservationRow($reservation)];
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
            $dayStartTime = $this->nullableTime5($data['dayStartTime'] ?? $data['day_start_time'] ?? null);
            $dayEndTime = $this->nullableTime5($data['dayEndTime'] ?? $data['day_end_time'] ?? null);

            $this->requireSitePermission($actor, $siteId, 'reservations.manage_vehicles');

            if ($name === '' || mb_strlen($name) > 160) {
                $this->fail('Nom de vehicule invalide', 400);
            }

            if (mb_strlen($description) > 255) {
                $this->fail('Description trop longue', 400);
            }

            if ($dayStartTime && $dayEndTime && $this->minutes5($dayEndTime) <= $this->minutes5($dayStartTime)) {
                $this->fail('Horaires du vehicule invalides', 400);
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
                'day_start_time' => $dayStartTime,
                'day_end_time' => $dayEndTime,
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

            $isCreator = (int) $reservation->user_id === (int) $actor->id;
            $canDeleteAny = $this->canOnSite($actor, (int) $reservation->site_id, 'reservations.delete_any');
            $canDeleteOwn = $isCreator
                && $this->canOnSite($actor, (int) $reservation->site_id, 'reservations.delete_own');

            if (! $canDeleteAny && ! $canDeleteOwn) {
                $this->fail('Suppression non autorisee', 403);
            }

            $reservation->delete();
            $this->dispatchReservationEvent($actor, 'deleted', $reservation);

            return ['ok' => true];
        });
    }

    private function requireNotPastStartDate(string $startAt): void
    {
        if (CarbonImmutable::parse($startAt)->startOfDay()->lt(CarbonImmutable::now()->startOfDay())) {
            $this->fail('Impossible de reserver dans le passe', 422);
        }
    }

    private function requireWithinVehicleHours(CrmVehicle $vehicle, CrmSite $site, string $startAt, string $endAt): void
    {
        if (! $vehicle->containsReservationPeriod($startAt, $endAt, $site)) {
            $this->fail('Creneau hors horaires du vehicule', 400);
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

    private function vehicleRow(CrmVehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'siteId' => (int) $vehicle->site_id,
            'name' => $vehicle->name,
            'description' => $vehicle->description ?? '',
            'color' => $vehicle->color ?: '#95002e',
            'photoUrl' => $vehicle->getAttribute('photo_url') ?? '',
            'dayStartTime' => $this->time5($vehicle->day_start_time, ''),
            'dayEndTime' => $this->time5($vehicle->day_end_time, ''),
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
        return CrmReferenceCache::activeSiteRows();
    }

    private function activeModuleRows(): array
    {
        return CrmReferenceCache::activeMenuModuleRows();
    }

    private function activeVehicleRows(): array
    {
        return CrmReferenceCache::activeVehicleRows();
    }

    private function permissionRows(): array
    {
        return CrmReferenceCache::permissionRows();
    }

    private function dispatchReservationEvent(CrmUser $actor, string $name, CrmReservation $reservation): void
    {
        CrmDomainEvent::dispatch(
            module: 'reservations',
            name: $name,
            entity: 'reservation',
            entityId: (int) $reservation->id,
            siteId: (int) $reservation->site_id,
            actorId: (int) $actor->id,
            actorName: $actor->name,
            payload: [
                'title' => $reservation->title ?? '',
                'vehicleId' => (int) $reservation->vehicle_id,
                'userId' => (int) $reservation->user_id,
                'userName' => $reservation->user_name ?? '',
                'startAt' => $reservation->start_at?->format('Y-m-d H:i:s') ?? '',
                'endAt' => $reservation->end_at?->format('Y-m-d H:i:s') ?? '',
                'actionUrl' => '/reservations',
            ],
        );
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

        return preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match) && (int) substr($match[1], 0, 2) <= 23
            ? $match[1]
            : $default;
    }

    private function nullableTime5(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match) || (int) substr($match[1], 0, 2) > 23) {
            $this->fail('Horaire du vehicule invalide', 400);
        }

        return $match[1];
    }

    private function minutes5(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
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
