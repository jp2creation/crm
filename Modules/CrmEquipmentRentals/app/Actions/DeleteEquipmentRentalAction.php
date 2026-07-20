<?php

namespace Modules\CrmEquipmentRentals\Actions;

use App\Models\CrmEquipmentRental;
use App\Models\CrmUser;
use Illuminate\Support\Facades\DB;
use Modules\CrmCore\Services\CrmActivityLogger;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeleteEquipmentRentalAction
{
    public function __construct(
        private readonly CrmActivityLogger $activity,
    ) {}

    public function execute(CrmUser $actor, int $rentalId): void
    {
        DB::transaction(function () use ($actor, $rentalId): void {
            $rental = CrmEquipmentRental::query()
                ->lockForUpdate()
                ->find($rentalId);

            if (! $rental) {
                $this->fail('Location introuvable', 404);
            }

            $rental->delete();
            $this->activity->log($actor, 'suppression location materiel', "Location materiel #{$rentalId}");
        });
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
