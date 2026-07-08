<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CrmEquipmentRental extends Model
{
    protected $table = 'crm_equipment_rentals';

    public const STATUS_RESERVED = 'reserved';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'site_id',
        'equipment_item_id',
        'user_id',
        'user_name',
        'period_type',
        'slot',
        'status',
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
        static::saving(function (CrmEquipmentRental $rental): void {
            $item = $rental->equipmentItem()->first();

            if ($item) {
                $rental->site_id = $item->site_id;
            }

            if ($rental->user_id) {
                $rental->user_name = $rental->user()->value('name') ?: $rental->user_name;
            }

            $rental->period_type = in_array($rental->period_type, ['half_day', 'day'], true)
                ? $rental->period_type
                : 'half_day';

            $rental->slot = in_array($rental->slot, ['morning', 'afternoon', 'full_day'], true)
                ? $rental->slot
                : ($rental->period_type === 'day' ? 'full_day' : 'morning');

            $rental->status = in_array($rental->status, array_keys(static::statusOptions()), true)
                ? $rental->status
                : static::STATUS_RESERVED;

            $startAt = $rental->start_at instanceof Carbon ? $rental->start_at : Carbon::parse($rental->start_at);
            $endAt = $rental->end_at instanceof Carbon ? $rental->end_at : Carbon::parse($rental->end_at);

            if ($endAt->lessThanOrEqualTo($startAt)) {
                throw ValidationException::withMessages([
                    'end_at' => 'La fin doit etre posterieure au debut.',
                ]);
            }

            $site = $rental->site_id ? CrmSite::query()->find($rental->site_id) : null;

            if ($site && ! $site->containsOpeningPeriod($startAt, $endAt)) {
                throw ValidationException::withMessages([
                    'start_at' => 'Ce creneau est hors horaires du site.',
                ]);
            }

            if ($rental->status === static::STATUS_CANCELLED) {
                return;
            }

            $hasConflict = static::query()
                ->where('equipment_item_id', $rental->equipment_item_id)
                ->where('status', '<>', static::STATUS_CANCELLED)
                ->when($rental->exists, fn (Builder $query) => $query->whereKeyNot($rental->getKey()))
                ->where('start_at', '<', $endAt)
                ->where('end_at', '>', $startAt)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'start_at' => 'Ce materiel est deja loue sur ce creneau.',
                ]);
            }
        });
    }

    public static function periodTypeOptions(): array
    {
        return [
            'half_day' => 'Demi-journee',
            'day' => 'Journee',
        ];
    }

    public static function slotOptions(): array
    {
        return [
            'morning' => 'Matin',
            'afternoon' => 'Apres-midi',
            'full_day' => 'Journee',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            static::STATUS_RESERVED => 'Reservee',
            static::STATUS_PICKED_UP => 'Sortie',
            static::STATUS_RETURNED => 'Retournee',
            static::STATUS_CANCELLED => 'Annulee',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function equipmentItem(): BelongsTo
    {
        return $this->belongsTo(CrmEquipmentItem::class, 'equipment_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'user_id');
    }
}
