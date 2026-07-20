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

class CreateEquipmentRentalAction
{
    public function __construct(
        private readonly ReservationConflictQuery $conflicts,
        private readonly CrmActivityLogger $activity,
    ) {}

    /**
     * @param  array{item: CrmEquipmentItem, site: CrmSite, periodType: string, slot: string, status: string, title: string, contactPhone: string, startAt: string, endAt: string, notes: string}  $payload
     */
    public function execute(CrmUser $actor, array $payload): CrmEquipmentRental
    {
        return DB::transaction(function () use ($actor, $payload): CrmEquipmentRental {
            $item = CrmEquipmentItem::query()
                ->active()
                ->lockForUpdate()
                ->find($payload['item']->id);

            if (! $item) {
                $this->fail('Materiel introuvable', 404);
            }

            if ($payload['status'] !== CrmEquipmentRental::STATUS_CANCELLED) {
                $this->requireNoRentalConflict((int) $item->id, $payload['startAt'], $payload['endAt']);
            }

            $rental = CrmEquipmentRental::query()->create([
                'site_id' => $payload['site']->id,
                'equipment_item_id' => $item->id,
                'user_id' => $actor->id,
                'user_name' => (string) $actor->getAttribute('name'),
                'period_type' => $payload['periodType'],
                'slot' => $payload['slot'],
                'status' => $payload['status'],
                'title' => $payload['title'],
                'contact_phone' => $payload['contactPhone'],
                'start_at' => $payload['startAt'],
                'end_at' => $payload['endAt'],
                'notes' => $payload['notes'],
            ]);

            $this->activity->log($actor, 'creation location materiel', "Location materiel #{$rental->id}");

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
