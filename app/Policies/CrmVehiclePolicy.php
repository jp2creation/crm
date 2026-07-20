<?php

namespace App\Policies;

use App\Models\CrmVehicle;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCrmSiteAccess;
use Illuminate\Auth\Access\Response;

class CrmVehiclePolicy
{
    use AuthorizesCrmSiteAccess;

    private const MODULE = 'reservations';

    public function viewAny(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['reservations.view']);
    }

    public function view(User $user, CrmVehicle $vehicle): bool
    {
        return $this->canOnSite($user, (int) $vehicle->getAttribute('site_id'), self::MODULE, ['reservations.view']);
    }

    public function create(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['reservations.manage_vehicles']);
    }

    public function createForSite(User $user, int $siteId): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['reservations.manage_vehicles']),
            'Droit insuffisant : reservations.manage_vehicles',
        );
    }

    public function update(User $user, CrmVehicle $vehicle): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, (int) $vehicle->getAttribute('site_id'), self::MODULE, ['reservations.manage_vehicles']),
            'Droit insuffisant : reservations.manage_vehicles',
        );
    }

    public function updateForSite(User $user, CrmVehicle $vehicle, int $siteId): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['reservations.manage_vehicles']),
            'Droit insuffisant : reservations.manage_vehicles',
        );
    }

    public function delete(User $user, CrmVehicle $vehicle): Response
    {
        return $this->update($user, $vehicle);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }

    private function allowIf(bool $allowed, string $message): Response
    {
        return $allowed ? Response::allow() : Response::deny($message);
    }
}
