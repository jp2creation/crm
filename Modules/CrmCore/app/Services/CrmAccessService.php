<?php

namespace Modules\CrmCore\Services;

use App\Models\CrmModule;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmUserSiteModulePermission;

class CrmAccessService
{
    public function hasModule(CrmUser $user, string $moduleSlug): bool
    {
        if ($user->role === 'blocked') {
            return false;
        }

        $module = $this->activeModule($moduleSlug);
        if (! $module) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $user->loadMissing('modules:id,slug,active');
        if ($user->modules->contains('id', $module->id)) {
            return true;
        }

        return $this->contextQuery($user)
            ->where('module_id', $module->id)
            ->exists();
    }

    public function hasPermission(CrmUser $user, string $permissionName): bool
    {
        if ($user->role === 'blocked') {
            return false;
        }

        $permissionId = $this->permissionId($permissionName);
        if (! $permissionId) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $user->loadMissing('permissions:id,name,label,sort_order');
        if ($user->permissions->contains('name', $permissionName)) {
            return true;
        }

        return $this->contextQuery($user)
            ->where('permission_id', $permissionId)
            ->exists();
    }

    public function canOnSite(CrmUser $user, int $siteId, string $moduleSlug, string $permissionName): bool
    {
        if ($user->role === 'blocked' || $siteId <= 0) {
            return false;
        }

        $module = $this->activeModule($moduleSlug);
        $permissionId = $this->permissionId($permissionName);

        if (! $module || ! $permissionId || ! $this->siteExists($siteId)) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($this->hasGlobalSiteModulePermission($user, $siteId, (int) $module->id, $permissionName)) {
            return true;
        }

        return $this->contextQuery($user)
            ->where('site_id', $siteId)
            ->where('module_id', $module->id)
            ->where('permission_id', $permissionId)
            ->exists();
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    public function canAnyOnSite(CrmUser $user, int $siteId, string $moduleSlug, array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->canOnSite($user, $siteId, $moduleSlug, $permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>|null  $permissionNames
     * @return array<int, int>
     */
    public function siteIdsForModule(CrmUser $user, string $moduleSlug, ?array $permissionNames = null): array
    {
        if ($user->role === 'blocked') {
            return [];
        }

        $module = $this->activeModule($moduleSlug);
        if (! $module) {
            return [];
        }

        if ($user->role === 'admin') {
            return $this->activeSiteIds();
        }

        $siteIds = [];

        $user->loadMissing(['sites:id', 'modules:id,slug,active', 'permissions:id,name,label,sort_order']);
        $globalModule = $user->modules->contains('id', $module->id);
        $globalPermissions = $permissionNames === null
            ? $user->permissions->isNotEmpty()
            : $user->permissions->contains(fn (CrmPermission $permission): bool => in_array($permission->name, $permissionNames, true));

        if ($globalModule && $globalPermissions) {
            $siteIds = $user->role === 'admin'
                ? $this->activeSiteIds()
                : $user->sites
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();
        }

        $contextPermissionIds = $permissionNames === null
            ? null
            : CrmPermission::query()->whereIn('name', $permissionNames)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $contextSiteIds = $this->contextQuery($user)
            ->where('module_id', $module->id)
            ->when(is_array($contextPermissionIds), fn ($query) => $query->whereIn('permission_id', $contextPermissionIds))
            ->pluck('site_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return collect([...$siteIds, ...$contextSiteIds])
            ->unique()
            ->filter(fn (int $siteId): bool => $this->siteExists($siteId))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function siteIds(CrmUser $user): array
    {
        if ($user->role === 'blocked') {
            return [];
        }

        if ($user->role === 'admin') {
            return $this->activeSiteIds();
        }

        $user->loadMissing('sites:id');
        $globalSiteIds = $user->sites
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $contextSiteIds = $this->contextQuery($user)
            ->pluck('site_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return collect([...$globalSiteIds, ...$contextSiteIds])
            ->unique()
            ->filter(fn (int $siteId): bool => $this->siteExists($siteId))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function moduleIds(CrmUser $user): array
    {
        if ($user->role === 'blocked') {
            return [];
        }

        if ($user->role === 'admin') {
            return CrmModule::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $user->loadMissing('modules:id,slug,active');
        $globalModuleIds = $user->modules
            ->filter(fn (CrmModule $module): bool => (bool) $module->active)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $contextModuleIds = $this->contextQuery($user)
            ->pluck('module_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return collect([...$globalModuleIds, ...$contextModuleIds])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function permissionNames(CrmUser $user): array
    {
        if ($user->role === 'blocked') {
            return [];
        }

        if ($user->role === 'admin') {
            return CrmPermission::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        $user->loadMissing('permissions:id,name,label,sort_order');
        $globalNames = $user->permissions
            ->pluck('name')
            ->all();

        $contextNames = CrmPermission::query()
            ->whereIn('id', $this->contextQuery($user)->pluck('permission_id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return collect([...$globalNames, ...$contextNames])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{siteId: int, moduleId: int, permissionId: int}>
     */
    public function accessRules(CrmUser $user): array
    {
        return $this->contextQuery($user)
            ->orderBy('site_id')
            ->orderBy('module_id')
            ->orderBy('permission_id')
            ->get(['site_id', 'module_id', 'permission_id'])
            ->map(fn (CrmUserSiteModulePermission $row): array => [
                'siteId' => (int) $row->site_id,
                'moduleId' => (int) $row->module_id,
                'permissionId' => (int) $row->permission_id,
            ])
            ->values()
            ->all();
    }

    private function hasGlobalSiteModulePermission(CrmUser $user, int $siteId, int $moduleId, string $permissionName): bool
    {
        $user->loadMissing(['sites:id', 'modules:id,slug,active', 'permissions:id,name,label,sort_order']);

        return $user->sites->contains('id', $siteId)
            && $this->hasGlobalModulePermission($user, $moduleId, $permissionName);
    }

    private function hasGlobalModulePermission(CrmUser $user, int $moduleId, string $permissionName): bool
    {
        $user->loadMissing(['modules:id,slug,active', 'permissions:id,name,label,sort_order']);

        return $user->modules->contains('id', $moduleId)
            && $user->permissions->contains('name', $permissionName);
    }

    private function activeModule(string $slug): ?CrmModule
    {
        return CrmModule::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->first(['id', 'slug', 'active']);
    }

    private function permissionId(string $name): ?int
    {
        $id = CrmPermission::query()->where('name', $name)->value('id');

        return $id ? (int) $id : null;
    }

    private function siteExists(int $siteId): bool
    {
        return CrmSite::query()
            ->active()
            ->whereKey($siteId)
            ->exists();
    }

    /**
     * @return array<int, int>
     */
    private function activeSiteIds(): array
    {
        return CrmSite::query()
            ->active()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function contextQuery(CrmUser $user)
    {
        return CrmUserSiteModulePermission::query()
            ->where('user_id', $user->id);
    }
}
