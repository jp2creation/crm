<?php

namespace App\Policies;

use App\Models\CrmEquipmentItem;
use App\Models\User;
use App\Policies\Concerns\AuthorizesCrmSiteAccess;
use Illuminate\Auth\Access\Response;

class CrmEquipmentItemPolicy
{
    use AuthorizesCrmSiteAccess;

    private const MODULE = 'locations-materiel';

    public function viewAny(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['equipment_rentals.view']);
    }

    public function view(User $user, CrmEquipmentItem $item): bool
    {
        return $this->canOnSite($user, (int) $item->getAttribute('site_id'), self::MODULE, ['equipment_rentals.view']);
    }

    public function create(User $user): bool
    {
        return $this->canAnySite($user, self::MODULE, ['equipment_rentals.manage_items']);
    }

    public function createForSite(User $user, int $siteId): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.manage_items']),
            'Droit insuffisant : equipment_rentals.manage_items',
        );
    }

    public function update(User $user, CrmEquipmentItem $item): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, (int) $item->getAttribute('site_id'), self::MODULE, ['equipment_rentals.manage_items']),
            'Droit insuffisant : equipment_rentals.manage_items',
        );
    }

    public function updateForSite(User $user, CrmEquipmentItem $item, int $siteId): Response
    {
        return $this->allowIf(
            $this->canOnSite($user, $siteId, self::MODULE, ['equipment_rentals.manage_items']),
            'Droit insuffisant : equipment_rentals.manage_items',
        );
    }

    public function delete(User $user, CrmEquipmentItem $item): Response
    {
        return $this->update($user, $item);
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
