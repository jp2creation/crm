<?php

namespace App\Policies;

use App\Models\CrmEquipmentRental;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCrmSiteAccess;
use Illuminate\Auth\Access\Response;

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
        return $this->canOnSite($user, (int) $rental->getAttribute('site_id'), self::MODULE, ['equipment_rentals.view']);
    }

    public function create(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['equipment_rentals.create']);
    }

    public function createForSite(User $user, int $siteId): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.create']),
            'Droit insuffisant : equipment_rentals.create',
        );
    }

    public function update(User $user, CrmEquipmentRental $rental): Response
    {
        $siteId = (int) $rental->getAttribute('site_id');

        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.update_any'])
            || (
                $this->ownsCrmRecord($user, $rental->getAttribute('user_id'))
                && $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.update_own'])
            ),
            'Modification non autorisee',
        );
    }

    public function updateForSite(User $user, CrmEquipmentRental $rental, int $siteId): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.update_any'])
            || (
                $this->ownsCrmRecord($user, $rental->getAttribute('user_id'))
                && $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.update_own'])
            ),
            'Site non autorise',
        );
    }

    public function delete(User $user, CrmEquipmentRental $rental): Response
    {
        $siteId = (int) $rental->getAttribute('site_id');

        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.delete_any'])
            || (
                $this->ownsCrmRecord($user, $rental->getAttribute('user_id'))
                && $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.delete_own'])
            ),
            'Suppression non autorisee',
        );
    }

    private function allowIf(bool $allowed, string $message): Response
    {
        return $allowed ? Response::allow() : Response::deny($message);
    }
}
