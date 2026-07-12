<?php

namespace App\Services\Crm;

use App\Models\CrmEquipmentCategory;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmModule;
use App\Models\CrmSite;
use App\Models\CrmUser;
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

class EquipmentRentalService
{
    public function __construct(
        private readonly ReservationConflictQuery $conflicts,
        private readonly CrmActivityLogger $activity,
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
        $rentals = CrmEquipmentRental::query()
            ->with(['equipmentItem:id,name', 'site:id,name', 'user:id,name'])
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
            'sites' => $this->activeSiteRows(),
            'modules' => $this->activeModuleRows(),
            'equipmentCategories' => $this->activeCategoryRows(),
            'equipmentItems' => $this->activeEquipmentItemRows(),
            'equipmentRentals' => $rentals->map(fn (CrmEquipmentRental $rental): array => $this->rentalRow($rental))->values()->all(),
            'permissions' => $this->permissionRows(),
            'users' => $users->map(fn (CrmUser $user): array => $this->userRow($user))->values()->all(),
        ];
    }

    public function createRental(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'equipment_rentals.create');

        return DB::transaction(function () use ($actor, $data): array {
            [$item, $site, $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes] = $this->rentalPayload($data);

            $this->requireSiteAccess($actor, (int) $site->id);

            if ($status !== CrmEquipmentRental::STATUS_CANCELLED) {
                $this->requireNoRentalConflict((int) $item->id, $startAt, $endAt);
            }

            $rental = CrmEquipmentRental::query()->create([
                'site_id' => $site->id,
                'equipment_item_id' => $item->id,
                'user_id' => $actor->id,
                'user_name' => $actor->name,
                'period_type' => $periodType,
                'slot' => $slot,
                'status' => $status,
                'title' => $title,
                'contact_phone' => $contactPhone,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $notes,
            ]);

            $this->log($actor, 'creation location materiel', "Location materiel #{$rental->id}");

            return ['ok' => true, 'equipmentRental' => $this->rentalRow($rental->refresh())];
        });
    }

    public function updateRental(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Location invalide', 400);
            }

            $rental = CrmEquipmentRental::query()
                ->lockForUpdate()
                ->find($id);

            if (! $rental) {
                $this->fail('Location introuvable', 404);
            }

            $this->requireSiteAccess($actor, (int) $rental->site_id);

            $canUpdateAny = $this->hasPermission($actor, 'equipment_rentals.update_any');
            $canUpdateOwn = $this->hasPermission($actor, 'equipment_rentals.update_own') && (int) $rental->user_id === (int) $actor->id;

            if (! $canUpdateAny && ! $canUpdateOwn) {
                $this->fail('Modification non autorisee', 403);
            }

            [$item, $site, $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes] = $this->rentalPayload($data);

            $this->requireSiteAccess($actor, (int) $site->id);

            if ($status !== CrmEquipmentRental::STATUS_CANCELLED) {
                $this->requireNoRentalConflict((int) $item->id, $startAt, $endAt, $id);
            }

            $rental->fill([
                'site_id' => $site->id,
                'equipment_item_id' => $item->id,
                'period_type' => $periodType,
                'slot' => $slot,
                'status' => $status,
                'title' => $title,
                'contact_phone' => $contactPhone,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $notes,
            ]);
            $rental->save();

            $this->log($actor, 'modification location materiel', "Location materiel #{$id}");

            return ['ok' => true, 'equipmentRental' => $this->rentalRow($rental->refresh())];
        });
    }

    public function deleteRental(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Location invalide', 400);
            }

            $rental = CrmEquipmentRental::query()
                ->lockForUpdate()
                ->find($id);

            if (! $rental) {
                $this->fail('Location introuvable', 404);
            }

            $this->requireSiteAccess($actor, (int) $rental->site_id);

            $canDeleteAny = $this->hasPermission($actor, 'equipment_rentals.delete_any');
            $canDeleteOwn = $this->hasPermission($actor, 'equipment_rentals.delete_own') && (int) $rental->user_id === (int) $actor->id;

            if (! $canDeleteAny && ! $canDeleteOwn) {
                $this->fail('Suppression non autorisee', 403);
            }

            $rental->delete();
            $this->log($actor, 'suppression location materiel', "Location materiel #{$id}");

            return ['ok' => true];
        });
    }

    public function saveEquipmentItem(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'equipment_rentals.manage_items');

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $siteId = (int) ($data['siteId'] ?? $data['site_id'] ?? 0);
            $categoryId = (int) ($data['categoryId'] ?? $data['category_id'] ?? 0);
            $categoryName = trim((string) ($data['categoryName'] ?? $data['category_name'] ?? ''));
            $name = trim((string) ($data['name'] ?? ''));
            $inventoryCode = trim((string) ($data['inventoryCode'] ?? $data['inventory_code'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $color = $this->color((string) ($data['color'] ?? '#95002e'));
            $photoDataUrl = (string) ($data['photoDataUrl'] ?? $data['photo_data_url'] ?? '');
            $halfDayPrice = $this->decimal($data['halfDayPrice'] ?? $data['half_day_price'] ?? 0);
            $dayPrice = $this->decimal($data['dayPrice'] ?? $data['day_price'] ?? 0);
            $showDayPrice = $this->boolean($data['showDayPrice'] ?? $data['show_day_price'] ?? true);
            $rentalMode = $this->rentalMode($data['rentalMode'] ?? $data['rental_mode'] ?? 'half_day_and_day');
            $depositAmount = $this->decimal($data['depositAmount'] ?? $data['deposit_amount'] ?? 0);
            $sortOrder = (int) ($data['sortOrder'] ?? $data['sort_order'] ?? 100);

            $this->requireSiteAccess($actor, $siteId);

            if ($name === '' || mb_strlen($name) > 160) {
                $this->fail('Nom du materiel invalide', 400);
            }

            if (mb_strlen($inventoryCode) > 80) {
                $this->fail('Code inventaire trop long', 400);
            }

            if (mb_strlen($description) > 255) {
                $this->fail('Description trop longue', 400);
            }

            if ($halfDayPrice < 0 || $dayPrice < 0 || $depositAmount < 0) {
                $this->fail('Les montants ne peuvent pas etre negatifs', 400);
            }

            $savedCategory = null;

            if ($categoryName !== '') {
                if (mb_strlen($categoryName) > 120) {
                    $this->fail('Categorie trop longue', 400);
                }

                $slug = $this->slug($categoryName);
                $category = CrmEquipmentCategory::query()
                    ->where('slug', $slug)
                    ->orWhere('name', $categoryName)
                    ->lockForUpdate()
                    ->first();

                if (! $category) {
                    $category = new CrmEquipmentCategory;
                    $category->sort_order = 100;
                }

                $category->fill([
                    'name' => $categoryName,
                    'slug' => $slug,
                    'active' => true,
                    'sort_order' => $category->sort_order ?: 100,
                ]);
                $category->save();

                $categoryId = (int) $category->id;
                $savedCategory = $this->categoryRow($category->refresh());
            } elseif ($categoryId > 0) {
                $category = CrmEquipmentCategory::query()
                    ->active()
                    ->find($categoryId);

                if (! $category) {
                    $this->fail('Categorie introuvable', 404);
                }

                $savedCategory = $this->categoryRow($category);
            } else {
                $categoryId = null;
            }

            if ($inventoryCode !== '') {
                $duplicateExists = CrmEquipmentItem::query()
                    ->where('inventory_code', $inventoryCode)
                    ->when($id > 0, fn ($query) => $query->whereKeyNot($id))
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateExists) {
                    $this->fail('Ce code inventaire existe deja', 409);
                }
            }

            $item = $id > 0
                ? CrmEquipmentItem::query()->lockForUpdate()->find($id)
                : new CrmEquipmentItem;

            if ($id > 0 && ! $item) {
                $this->fail('Materiel introuvable', 404);
            }

            if ($id > 0) {
                $this->requireSiteAccess($actor, (int) $item->site_id);
            }

            $photoUrl = $item->getAttribute('photo_url') ?: null;

            if (trim($photoDataUrl) !== '') {
                $photoUrl = $this->saveDataImage($photoDataUrl, 'equipment') ?: $photoUrl;
            }

            $item->fill([
                'site_id' => $siteId,
                'category_id' => $categoryId,
                'name' => $name,
                'inventory_code' => $inventoryCode ?: null,
                'description' => $description,
                'color' => $color,
                'photo_url' => $photoUrl,
                'half_day_price' => $halfDayPrice,
                'day_price' => $dayPrice,
                'show_day_price' => $showDayPrice,
                'rental_mode' => $rentalMode,
                'deposit_amount' => $depositAmount,
                'active' => true,
                'sort_order' => $sortOrder,
            ]);
            $item->save();

            $this->log($actor, $id > 0 ? 'modification materiel' : 'creation materiel', "Materiel #{$item->id}");

            return [
                'ok' => true,
                'equipmentItem' => $this->itemRow($item->refresh()),
                'equipmentCategory' => $savedCategory,
            ];
        });
    }

    public function deleteEquipmentItem(CrmUser $actor, array $data): array
    {
        $this->requireModule($actor);
        $this->requirePermission($actor, 'equipment_rentals.manage_items');

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->fail('Materiel invalide', 400);
            }

            $item = CrmEquipmentItem::query()
                ->lockForUpdate()
                ->find($id);

            if (! $item) {
                $this->fail('Materiel introuvable', 404);
            }

            $this->requireSiteAccess($actor, (int) $item->site_id);
            $item->forceFill(['active' => false])->save();

            $this->log($actor, 'masquage materiel', "Materiel #{$id}");

            return ['ok' => true, 'id' => $id];
        });
    }

    /**
     * @return array{0: CrmEquipmentItem, 1: CrmSite, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}
     */
    private function rentalPayload(array $data): array
    {
        $itemId = (int) ($data['equipmentItemId'] ?? $data['equipment_item_id'] ?? 0);

        if ($itemId <= 0) {
            $this->fail('Materiel invalide', 400);
        }

        $item = CrmEquipmentItem::query()
            ->active()
            ->lockForUpdate()
            ->find($itemId);

        if (! $item) {
            $this->fail('Materiel introuvable', 404);
        }

        $site = CrmSite::query()->find((int) $item->site_id);

        if (! $site) {
            $this->fail('Site introuvable', 404);
        }

        [$periodType, $slot, $startAt, $endAt] = $this->slotRange($data, $site);

        if ($this->rentalMode($item->getAttribute('rental_mode') ?? 'half_day_and_day') === 'day_only') {
            [, , $expectedStartAt, $expectedEndAt] = $this->slotRange([
                'date' => CarbonImmutable::parse($startAt)->format('Y-m-d'),
                'periodType' => 'day',
                'slot' => 'full_day',
            ], $site);

            if (
                $periodType !== 'day'
                || $slot !== 'full_day'
                || strtotime($startAt) !== strtotime($expectedStartAt)
                || strtotime($endAt) !== strtotime($expectedEndAt)
            ) {
                $this->fail('Ce materiel se loue uniquement a la journee', 400);
            }
        }

        if (strtotime($endAt) <= strtotime($startAt)) {
            $this->fail('Creneau invalide', 400);
        }

        if (! $site->containsOpeningPeriod($startAt, $endAt)) {
            $this->fail('Creneau hors horaires du site', 400);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $contactPhone = trim((string) ($data['contactPhone'] ?? $data['contact_phone'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $status = trim((string) ($data['status'] ?? CrmEquipmentRental::STATUS_RESERVED));

        if (! in_array($status, array_keys(CrmEquipmentRental::statusOptions()), true)) {
            $status = CrmEquipmentRental::STATUS_RESERVED;
        }

        if (mb_strlen($title) > 190) {
            $this->fail('Titre trop long', 400);
        }

        if (mb_strlen($contactPhone) > 40) {
            $this->fail('Telephone trop long', 400);
        }

        return [$item, $site, $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes];
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function slotRange(array $data, CrmSite $site): array
    {
        $periodType = in_array(($data['periodType'] ?? $data['period_type'] ?? ''), ['half_day', 'day'], true)
            ? (string) ($data['periodType'] ?? $data['period_type'])
            : 'half_day';

        $slot = in_array(($data['slot'] ?? ''), ['morning', 'afternoon', 'full_day'], true)
            ? (string) $data['slot']
            : ($periodType === 'day' ? 'full_day' : 'morning');

        if ($periodType === 'day' || $slot === 'full_day') {
            $periodType = 'day';
            $slot = 'full_day';
        }

        $startRaw = (string) ($data['startAt'] ?? $data['start_at'] ?? '');
        $endRaw = (string) ($data['endAt'] ?? $data['end_at'] ?? '');

        if ($startRaw !== '' && $endRaw !== '') {
            return [$periodType, $slot, $this->normalizeDateTime($startRaw), $this->normalizeDateTime($endRaw)];
        }

        $date = trim((string) ($data['date'] ?? ''));
        $time = $date === '' ? false : strtotime($date);

        if ($time === false) {
            $this->fail('Date de location invalide', 400);
        }

        $day = date('Y-m-d', $time);
        $hours = $this->siteHours($site);

        if ($slot === 'afternoon') {
            return [$periodType, $slot, "{$day} {$hours['afternoonStart']}:00", "{$day} {$hours['afternoonEnd']}:00"];
        }

        if ($slot === 'full_day') {
            return ['day', 'full_day', "{$day} {$hours['morningStart']}:00", "{$day} {$hours['afternoonEnd']}:00"];
        }

        return [$periodType, 'morning', "{$day} {$hours['morningStart']}:00", "{$day} {$hours['morningEnd']}:00"];
    }

    private function rentalMode(mixed $value): string
    {
        return in_array($value, ['half_day_and_day', 'day_only'], true)
            ? (string) $value
            : 'half_day_and_day';
    }

    private function requireNoRentalConflict(int $itemId, string $startAt, string $endAt, ?int $ignoreId = null): void
    {
        if ($this->conflicts->equipmentOverlaps($itemId, $startAt, $endAt, $ignoreId)) {
            $this->fail('Ce materiel est deja loue sur ce creneau', 409);
        }
    }

    private function requireModule(CrmUser $actor): void
    {
        if (! $this->hasModule($actor)) {
            $this->fail('Module non autorise : locations-materiel', 403);
        }
    }

    private function hasModule(CrmUser $actor): bool
    {
        if ($actor->role === 'blocked') {
            return false;
        }

        $actor->loadMissing('modules:id,slug,active');
        $module = CrmModule::query()
            ->where('slug', 'locations-materiel')
            ->where('active', true)
            ->first();

        return $module !== null && $actor->modules->contains('id', $module->id);
    }

    private function requirePermission(CrmUser $actor, string $permission): void
    {
        if (! $this->hasPermission($actor, $permission)) {
            $this->fail('Droit insuffisant : '.$permission, 403);
        }
    }

    private function hasPermission(CrmUser $actor, string $permission): bool
    {
        if ($actor->role === 'blocked') {
            return false;
        }

        $actor->loadMissing('permissions:id,name,label');

        return $actor->permissions->contains('name', $permission);
    }

    private function requireSiteAccess(CrmUser $actor, int $siteId): void
    {
        if ($siteId <= 0 || ! in_array($siteId, $this->siteIds($actor), true)) {
            $this->fail('Site non autorise', 403);
        }
    }

    private function actorRow(CrmUser $actor): array
    {
        return [
            'id' => $actor->id,
            'name' => $actor->name,
            'role' => $actor->role,
            'active' => (bool) $actor->active,
            'siteIds' => $this->siteIds($actor),
            'moduleIds' => $actor->role === 'blocked' ? [] : $this->moduleIds($actor),
            'permissions' => $actor->role === 'blocked' ? [] : $this->permissionNames($actor),
        ];
    }

    private function userRow(CrmUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'siteIds' => $this->siteIds($user),
            'moduleIds' => $this->moduleIds($user),
            'permissions' => $this->permissionNames($user),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function siteIds(CrmUser $user): array
    {
        if ($user->relationLoaded('sites')) {
            return $user->sites
                ->sortBy([
                    fn ($siteA, $siteB): int => (int) ($siteB->pivot?->is_default ?? false) <=> (int) ($siteA->pivot?->is_default ?? false),
                    fn ($siteA, $siteB): int => (int) $siteA->id <=> (int) $siteB->id,
                ])
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        }

        return $user->sites()
            ->whereNull('crm_sites.deleted_at')
            ->orderByDesc('crm_user_sites.is_default')
            ->orderBy('crm_sites.id')
            ->pluck('crm_sites.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function moduleIds(CrmUser $user): array
    {
        if ($user->relationLoaded('modules')) {
            return $user->modules
                ->sortBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        }

        return $user->modules()
            ->orderBy('crm_modules.id')
            ->pluck('crm_modules.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function permissionNames(CrmUser $user): array
    {
        if ($user->relationLoaded('permissions')) {
            return $user->permissions
                ->sortBy([
                    fn ($permissionA, $permissionB): int => (int) $permissionA->sort_order <=> (int) $permissionB->sort_order,
                    fn ($permissionA, $permissionB): int => strcmp((string) $permissionA->name, (string) $permissionB->name),
                ])
                ->pluck('name')
                ->values()
                ->all();
        }

        return $user->permissions()
            ->orderBy('crm_permissions.sort_order')
            ->orderBy('crm_permissions.name')
            ->pluck('crm_permissions.name')
            ->values()
            ->all();
    }

    private function siteRow(CrmSite $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'slug' => $site->slug,
            'hours' => $this->siteHours($site),
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
            return CrmModule::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CrmModule $module): array => $this->moduleRow($module))
                ->values()
                ->all();
        });
    }

    private function categoryRow(CrmEquipmentCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'active' => (bool) $category->active,
            'sortOrder' => (int) $category->sort_order,
        ];
    }

    private function activeCategoryRows(): array
    {
        return Cache::rememberForever(CrmReferenceCache::ACTIVE_EQUIPMENT_CATEGORY_ROWS, function (): array {
            return CrmEquipmentCategory::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CrmEquipmentCategory $category): array => $this->categoryRow($category))
                ->values()
                ->all();
        });
    }

    private function activeEquipmentItemRows(): array
    {
        return Cache::rememberForever(CrmReferenceCache::ACTIVE_EQUIPMENT_ITEM_ROWS, function (): array {
            return CrmEquipmentItem::query()
                ->active()
                ->orderBy('site_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CrmEquipmentItem $item): array => $this->itemRow($item))
                ->values()
                ->all();
        });
    }

    private function itemRow(CrmEquipmentItem $item): array
    {
        return [
            'id' => $item->id,
            'siteId' => (int) $item->site_id,
            'categoryId' => $item->category_id ? (int) $item->category_id : null,
            'name' => $item->name,
            'inventoryCode' => $item->inventory_code ?? '',
            'description' => $item->description ?? '',
            'color' => $item->color ?: '#95002e',
            'photoUrl' => $item->getAttribute('photo_url') ?? '',
            'halfDayPrice' => (float) $item->half_day_price,
            'dayPrice' => (float) $item->day_price,
            'showDayPrice' => (bool) ($item->show_day_price ?? true),
            'rentalMode' => $this->rentalMode($item->getAttribute('rental_mode') ?? 'half_day_and_day'),
            'depositAmount' => (float) $item->deposit_amount,
            'active' => (bool) $item->active,
            'sortOrder' => (int) $item->sort_order,
        ];
    }

    private function rentalRow(CrmEquipmentRental $rental): array
    {
        return [
            'id' => $rental->id,
            'siteId' => (int) $rental->site_id,
            'equipmentItemId' => (int) $rental->equipment_item_id,
            'userId' => (int) $rental->user_id,
            'userName' => $rental->user_name,
            'periodType' => $rental->period_type ?? 'half_day',
            'slot' => $rental->slot ?? 'morning',
            'status' => $rental->status ?? CrmEquipmentRental::STATUS_RESERVED,
            'title' => $rental->title ?? '',
            'contactPhone' => $rental->contact_phone ?? '',
            'startAt' => $rental->start_at?->format('Y-m-d\TH:i') ?? '',
            'endAt' => $rental->end_at?->format('Y-m-d\TH:i') ?? '',
            'notes' => $rental->notes ?? '',
        ];
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

    /**
     * @return array{morningStart: string, morningEnd: string, afternoonStart: string, afternoonEnd: string}
     */
    private function siteHours(CrmSite $site): array
    {
        return [
            'morningStart' => $this->time5($site->morning_start, '07:30'),
            'morningEnd' => $this->time5($site->morning_end, '12:00'),
            'afternoonStart' => $this->time5($site->afternoon_start, '13:30'),
            'afternoonEnd' => $this->time5($site->afternoon_end, '17:30'),
        ];
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

    private function decimal(mixed $value): float
    {
        return (float) str_replace(',', '.', (string) $value);
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function color(string $value): string
    {
        $value = trim($value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '#95002e';
    }

    private function slug(string $value): string
    {
        return Str::slug($value) ?: 'item';
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
