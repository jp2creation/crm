<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int|null $site_id
 * @property int|null $vehicle_id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $title
 * @property string|null $contact_phone
 * @property Carbon|null $start_at
 * @property Carbon|null $end_at
 * @property string|null $notes
 * @property-read CrmSite|null $site
 * @property-read CrmVehicle|null $vehicle
 * @property-read CrmUser|null $user
 */
class CrmReservation extends Model
{
    protected $table = 'crm_reservations';

    protected $fillable = [
        'site_id',
        'vehicle_id',
        'user_id',
        'user_name',
        'title',
        'contact_phone',
        'start_at',
        'end_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CrmReservation $reservation): void {
            $vehicle = null;

            if ($reservation->vehicle_id) {
                $vehicle = CrmVehicle::query()->find($reservation->vehicle_id);

                if ($vehicle) {
                    $reservation->site_id = $vehicle->site_id;
                }
            }

            if ($reservation->user_id) {
                $user = CrmUser::query()->find($reservation->user_id);

                if ($user) {
                    $reservation->user_name = $user->name;
                }
            }

            if ($reservation->start_at && $reservation->end_at && $reservation->end_at->lessThanOrEqualTo($reservation->start_at)) {
                throw ValidationException::withMessages([
                    'end_at' => 'La fin doit etre apres le debut.',
                ]);
            }

            if ($reservation->site_id && $reservation->start_at && $reservation->end_at) {
                $site = CrmSite::query()->find($reservation->site_id);

                if ($site) {
                    if ($vehicle) {
                        if (! $vehicle->containsReservationPeriod($reservation->start_at, $reservation->end_at, $site)) {
                            throw ValidationException::withMessages([
                                'start_at' => 'Ce creneau est hors horaires du vehicule.',
                            ]);
                        }
                    } elseif (! $site->containsOpeningPeriod($reservation->start_at, $reservation->end_at)) {
                        throw ValidationException::withMessages([
                            'start_at' => 'Ce creneau est hors horaires du site.',
                        ]);
                    }
                }
            }

            if ($reservation->vehicle_id && $reservation->start_at && $reservation->end_at) {
                $conflictExists = static::query()
                    ->where('vehicle_id', $reservation->vehicle_id)
                    ->when($reservation->exists, fn ($query) => $query->whereKeyNot($reservation->getKey()))
                    ->where(function ($query) use ($reservation): void {
                        $query
                            ->where('end_at', '>', $reservation->start_at)
                            ->where('start_at', '<', $reservation->end_at);
                    })
                    ->exists();

                if ($conflictExists) {
                    throw ValidationException::withMessages([
                        'start_at' => 'Ce vehicule est deja reserve sur ce creneau.',
                    ]);
                }
            }
        });
    }

    /**
     * @return BelongsTo<CrmSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    /**
     * @return BelongsTo<CrmVehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CrmVehicle::class, 'vehicle_id');
    }

    /**
     * @return BelongsTo<CrmUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'user_id');
    }
}
