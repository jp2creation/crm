<?php

namespace App\Policies\Concerns;

use App\Models\CrmUser;
use App\Models\User;
use Modules\CrmCore\Services\CrmAccessService;

trait AuthorizesCrmSiteAccess
{
    protected function crmUser(User $user): ?CrmUser
    {
        return $user->crmUser()
            ->with(['modules:id,slug,active', 'permissions:id,name,label,sort_order', 'sites:id'])
            ->first();
    }

    protected function canUseFilamentAdmin(User $user): bool
    {
        return $user->canUsePlatformAdministration();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function canAnySite(User $user, string $moduleSlug, array $permissions): bool
    {
        if ($this->canUseFilamentAdmin($user)) {
            return true;
        }

        $crmUser = $this->crmUser($user);

        return $crmUser instanceof CrmUser
            && app(CrmAccessService::class)->siteIdsForModule($crmUser, $moduleSlug, $permissions) !== [];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function canOnSite(User $user, int $siteId, string $moduleSlug, array $permissions): bool
    {
        if ($siteId <= 0) {
            return false;
        }

        if ($this->canUseFilamentAdmin($user)) {
            return true;
        }

        $crmUser = $this->crmUser($user);

        if (! $crmUser instanceof CrmUser) {
            return false;
        }

        $access = app(CrmAccessService::class);

        foreach ($permissions as $permission) {
            if ($access->canOnSite($crmUser, $siteId, $moduleSlug, $permission)) {
                return true;
            }
        }

        return false;
    }

    protected function ownsCrmRecord(User $user, ?int $crmUserId): bool
    {
        if (! $crmUserId) {
            return false;
        }

        $crmUser = $this->crmUser($user);

        return $crmUser instanceof CrmUser && (int) $crmUser->id === (int) $crmUserId;
    }
}
