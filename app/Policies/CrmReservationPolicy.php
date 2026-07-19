<?php

namespace App\Policies;

use App\Models\CrmReservation;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCrmSiteAccess;

class CrmReservationPolicy
{
    use AuthorizesCrmSiteAccess;

    private const MODULE = 'reservations';

    public function viewAny(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['reservations.view']);
    }

    public function view(User $user, CrmReservation $reservation): bool
    {
        return $this->canOnSite($user, (int) $reservation->site_id, self::MODULE, ['reservations.view']);
    }

    public function create(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['reservations.create']);
    }

    public function createForSite(User $user, int $siteId): bool
    {
        return $this->canOnSite($user, $siteId, self::MODULE, ['reservations.create']);
    }

    public function update(User $user, CrmReservation $reservation): bool
    {
        $siteId = (int) $reservation->site_id;

        return $this->canOnSite($user, $siteId, self::MODULE, ['reservations.update_any'])
            || (
                $this->ownsCrmRecord($user, $reservation->user_id)
                && $this->canOnSite($user, $siteId, self::MODULE, ['reservations.update_own'])
            );
    }

    public function delete(User $user, CrmReservation $reservation): bool
    {
        $siteId = (int) $reservation->site_id;

        return $this->canOnSite($user, $siteId, self::MODULE, ['reservations.delete_any'])
            || (
                $this->ownsCrmRecord($user, $reservation->user_id)
                && $this->canOnSite($user, $siteId, self::MODULE, ['reservations.delete_own'])
            );
    }
}
