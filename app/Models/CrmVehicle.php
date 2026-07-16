<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\CrmCore\Services\UploadedCrmFileCleaner;
use Modules\CrmCore\Support\CrmReferenceCache;

class CrmVehicle extends Model
{
    protected $table = 'crm_vehicles';

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'color',
        'photo_url',
        'day_start_time',
        'day_end_time',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CrmVehicle $vehicle): void {
            $vehicle->day_start_time = static::normalizeTime($vehicle->day_start_time);
            $vehicle->day_end_time = static::normalizeTime($vehicle->day_end_time);

            $site = $vehicle->site_id ? CrmSite::query()->find($vehicle->site_id) : null;
            $hours = $vehicle->dailyReservationHours($site);

            if (static::minutes($hours['end'], '19:00') <= static::minutes($hours['start'], '06:00')) {
                throw ValidationException::withMessages([
                    'day_end_time' => 'L heure de fermeture du vehicule doit etre apres l heure d ouverture.',
                ]);
            }
        });

        static::saved(function (): void {
            CrmReferenceCache::forgetVehicles();
        });

        static::updated(function (CrmVehicle $vehicle): void {
            if ($vehicle->wasChanged('photo_url')) {
                app(UploadedCrmFileCleaner::class)->deletePublicUpload($vehicle->getOriginal('photo_url'));
            }

            if ($vehicle->wasChanged('active') && ! $vehicle->active) {
                app(UploadedCrmFileCleaner::class)->deletePublicUpload($vehicle->getAttribute('photo_url'));
            }
        });

        static::deleting(function (CrmVehicle $vehicle): void {
            if ($vehicle->reservations()->exists()) {
                throw ValidationException::withMessages([
                    'vehicle' => 'Ce vehicule a des reservations. Desactive-le pour le masquer.',
                ]);
            }
        });

        static::deleted(function (CrmVehicle $vehicle): void {
            app(UploadedCrmFileCleaner::class)->deletePublicUpload($vehicle->getAttribute('photo_url'));
            CrmReferenceCache::forgetVehicles();
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(CrmReservation::class, 'vehicle_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return array{start: string, end: string}
     */
    public function dailyReservationHours(?CrmSite $site = null): array
    {
        return [
            'start' => static::time5($this->day_start_time, '06:00'),
            'end' => static::time5($this->day_end_time, '19:00'),
        ];
    }

    public function reservationHoursLabel(?CrmSite $site = null): string
    {
        $hours = $this->dailyReservationHours($site);

        return $hours['start'].'-'.$hours['end'];
    }

    public function containsReservationPeriod(mixed $startAt, mixed $endAt, ?CrmSite $site = null): bool
    {
        $start = $startAt instanceof Carbon ? $startAt : Carbon::parse($startAt);
        $end = $endAt instanceof Carbon ? $endAt : Carbon::parse($endAt);

        if (! $start->isSameDay($end)) {
            return false;
        }

        $hours = $this->dailyReservationHours($site);
        $startMinute = ($start->hour * 60) + $start->minute;
        $endMinute = ($end->hour * 60) + $end->minute;
        $allowedStart = static::minutes($hours['start'], '06:00');
        $allowedEnd = static::minutes($hours['end'], '19:00');

        return $endMinute > $startMinute
            && $startMinute >= $allowedStart
            && $endMinute <= $allowedEnd;
    }

    private static function normalizeTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^([0-2][0-9]):([0-5][0-9])/', $value, $match) || (int) substr($match[1], 0, 2) > 23) {
            throw ValidationException::withMessages([
                'day_start_time' => 'Horaire de vehicule invalide.',
            ]);
        }

        return $match[1].':'.$match[2];
    }

    private static function time5(?string $value, string $default): string
    {
        $value = trim((string) $value);

        return preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match) && (int) substr($match[1], 0, 2) <= 23
            ? $match[1]
            : substr((string) $default, 0, 5);
    }

    private static function minutes(?string $time, string $default): int
    {
        $time = static::time5($time, $default);
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }
}
