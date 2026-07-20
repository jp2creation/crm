<?php

namespace Modules\CrmEquipmentRentals\Actions;

use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmSite;
use App\Models\CrmUser;
use Illuminate\Support\Facades\DB;
use Modules\CrmCore\Queries\ReservationConflictQuery;
use Modules\CrmCore\Services\CrmActivityLogger;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateEquipmentRentalAction
{
    public function __construct(
        private readonly ReservationConflictQuery $conflicts,
        private readonly CrmActivityLogger $activity,
    ) {}

    /**
     * @param  array{item: CrmEquipmentItem, site: CrmSite, periodType: string, slot: string, status: string, title: string, contactPhone: string, startAt: string, endAt: string, notes: string}  $payload
     */
    public function execute(CrmUser $actor, int $rentalId, array $payload): CrmEquipmentRental
    {
        return DB::transaction(function () use ($actor, $rentalId, $payload): CrmEquipmentRental {
            $rental = CrmEquipmentRental::query()
                ->lockForUpdate()
                ->find($rentalId);

            if (! $rental) {
                $this->fail('Location introuvable', 404);
            }

            $item = CrmEquipmentItem::query()
                ->active()
                ->lockForUpdate()
                ->find($payload['item']->id);

            if (! $item) {
                $this->fail('Materiel introuvable', 404);
            }

            if ($payload['status'] !== CrmEquipmentRental::STATUS_CANCELLED) {
                $this->requireNoRentalConflict((int) $item->id, $payload['startAt'], $payload['endAt'], $rentalId);
            }

            $rental->fill([
                'site_id' => $payload['site']->id,
                'equipment_item_id' => $item->id,
                'period_type' => $payload['periodType'],
                'slot' => $payload['slot'],
                'status' => $payload['status'],
                'title' => $payload['title'],
                'contact_phone' => $payload['contactPhone'],
                'start_at' => $payload['startAt'],
                'end_at' => $payload['endAt'],
                'notes' => $payload['notes'],
            ]);
            $rental->save();

            $this->activity->log($actor, 'modification location materiel', "Location materiel #{$rentalId}");

            return $rental->refresh();
        });
    }

    private function requireNoRentalConflict(int $itemId, string $startAt, string $endAt, ?int $ignoreId = null): void
    {
        if ($this->conflicts->equipmentOverlaps($itemId, $startAt, $endAt, $ignoreId)) {
            $this->fail('Ce materiel est deja loue sur ce creneau', 409);
        }
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
