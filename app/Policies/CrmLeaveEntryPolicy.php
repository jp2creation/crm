<?php

namespace App\Policies;

use App\Models\CrmLeaveEntry;
use App\Models\CrmUser;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCrmSiteAccess;

class CrmLeaveEntryPolicy
{
    use AuthorizesCrmSiteAccess;

    private const MODULE = 'conges';

    public function viewAny(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['conges.view', 'conges.manage']);
    }

    public function view(User $user, CrmLeaveEntry $entry): bool
    {
        return $this->canEntrySite($user, $entry, ['conges.view', 'conges.manage']);
    }

    public function create(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['conges.manage']);
    }

    public function createForSite(User $user, int $siteId): bool
    {
        return $this->canOnSite($user, $siteId, self::MODULE, ['conges.manage']);
    }

    public function update(User $user, CrmLeaveEntry $entry): bool
    {
        return $this->canEntrySite($user, $entry, ['conges.manage']);
    }

    public function delete(User $user, CrmLeaveEntry $entry): bool
    {
        return $this->canEntrySite($user, $entry, ['conges.manage']);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function canEntrySite(User $user, CrmLeaveEntry $entry, array $permissions): bool
    {
        if ($this->canUseFilamentAdmin($user)) {
            return true;
        }

        $entry->loadMissing('employee.crmUser.sites:id');
        $employeeUser = $entry->employee?->crmUser;

        if (! $employeeUser instanceof CrmUser) {
            return false;
        }

        foreach ($employeeUser->sites as $site) {
            if ($this->canOnSite($user, (int) $site->id, self::MODULE, $permissions)) {
                return true;
            }
        }

        return false;
    }
}
