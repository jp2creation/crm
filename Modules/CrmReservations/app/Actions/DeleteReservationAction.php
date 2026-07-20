<?php

namespace Modules\CrmReservations\Actions;

use App\Models\CrmReservation;
use App\Models\CrmUser;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Modules\CrmCore\Events\CrmDomainEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeleteReservationAction
{
    public function execute(CrmUser $actor, int $reservationId): void
    {
        DB::transaction(function () use ($actor, $reservationId): void {
            $reservation = CrmReservation::query()
                ->lockForUpdate()
                ->find($reservationId);

            if (! $reservation) {
                $this->fail('Reservation introuvable', 404);
            }

            $reservation->delete();
            $this->dispatchReservationEvent($actor, 'deleted', $reservation);
        });
    }

    private function dispatchReservationEvent(CrmUser $actor, string $name, CrmReservation $reservation): void
    {
        CrmDomainEvent::dispatch(
            module: 'reservations',
            name: $name,
            entity: 'reservation',
            entityId: (int) $reservation->id,
            siteId: (int) $reservation->getAttribute('site_id'),
            actorId: (int) $actor->id,
            actorName: (string) $actor->getAttribute('name'),
            payload: [
                'title' => (string) ($reservation->getAttribute('title') ?? ''),
                'vehicleId' => (int) $reservation->getAttribute('vehicle_id'),
                'userId' => (int) $reservation->getAttribute('user_id'),
                'userName' => (string) ($reservation->getAttribute('user_name') ?? ''),
                'startAt' => $this->dateTimePayload($reservation->getAttribute('start_at')),
                'endAt' => $this->dateTimePayload($reservation->getAttribute('end_at')),
                'actionUrl' => '/reservations',
            ],
        );
    }

    private function dateTimePayload(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) $value;
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
