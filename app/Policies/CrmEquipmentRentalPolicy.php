<?php

namespace App\Policies;

use App\Models\CrmEquipmentRental;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCrmSiteAccess;

class CrmEquipmentRentalPolicy
{
    use AuthorizesCrmSiteAccess;

    private const MODULE = 'locations-materiel';

    public function viewAny(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['equipment_rentals.view']);
    }

    public function view(User $user, CrmEquipmentRental $rental): bool
    {
        return $this->canOnSite($user, (int) $rental->site_id, self::MODULE, ['equipment_rentals.view']);
    }

    public function create(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['equipment_rentals.create']);
    }

    public function createForSite(User $user, int $siteId): bool
    {
        return $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.create']);
    }

    public function update(User $user, CrmEquipmentRental $rental): bool
    {
        $siteId = (int) $rental->site_id;

        return $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.update_any'])
            || (
                $this->ownsCrmRecord($user, $rental->user_id)
                && $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.update_own'])
            );
    }

    public function delete(User $user, CrmEquipmentRental $rental): bool
    {
        $siteId = (int) $rental->site_id;

        return $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.delete_any'])
            || (
                $this->ownsCrmRecord($user, $rental->user_id)
                && $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.delete_own'])
            );
    }
}
