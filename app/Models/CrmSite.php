<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CrmCore\Support\CrmReferenceCache;

class CrmSite extends Model
{
    use SoftDeletes;

    protected $table = 'crm_sites';

    protected $fillable = [
        'name',
        'slug',
        'active',
        'morning_start',
        'morning_end',
        'afternoon_start',
        'afternoon_end',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CrmSite $site): void {
            if (blank($site->slug) || $site->isDirty('name')) {
                $site->slug = static::uniqueSlug($site->name, $site->getKey());
            }
        });

        static::deleting(function (CrmSite $site): void {
            $site->users()->detach();
            $site->siteModulePermissions()->delete();

            if (! $site->isForceDeleting()) {
                $site->forceFill([
                    'name' => Str::limit((string) $site->name, 100, '').' supprime '.$site->getKey(),
                    'slug' => Str::limit((string) ($site->slug ?: Str::slug($site->name)), 110, '').'-deleted-'.$site->getKey(),
                    'active' => false,
                ])->saveQuietly();

                return;
            }

            if (
                $site->vehicles()->exists()
                || $site->reservations()->exists()
                || $site->equipmentItems()->exists()
                || $site->equipmentRentals()->exists()
                || $site->cashRegisterDays()->exists()
            ) {
                throw ValidationException::withMessages([
                    'site' => 'Ce site est utilise par des vehicules, du materiel, des reservations ou des caisses. Supprime-le sans forcer pour l archiver.',
                ]);
            }
        });

        static::saved(function (): void {
            CrmReferenceCache::forgetSites();
        });

        static::deleted(function (): void {
            CrmReferenceCache::forgetSites();
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'site';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CrmVehicle::class, 'site_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(CrmReservation::class, 'site_id');
    }

    public function equipmentItems(): HasMany
    {
        return $this->hasMany(CrmEquipmentItem::class, 'site_id');
    }

    public function equipmentRentals(): HasMany
    {
        return $this->hasMany(CrmEquipmentRental::class, 'site_id');
    }

    public function cashRegisterDays(): HasMany
    {
        return $this->hasMany(CrmCashRegisterDay::class, 'site_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_sites', 'site_id', 'user_id')
            ->withPivot('is_default');
    }

    public function siteModulePermissions(): HasMany
    {
        return $this->hasMany(CrmUserSiteModulePermission::class, 'site_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public static function options(): array
    {
        return static::query()
            ->orderByDesc('active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function openingHoursLabel(): string
    {
        return sprintf(
            '%s-%s / %s-%s',
            substr((string) ($this->morning_start ?: '07:30'), 0, 5),
            substr((string) ($this->morning_end ?: '12:00'), 0, 5),
            substr((string) ($this->afternoon_start ?: '13:30'), 0, 5),
            substr((string) ($this->afternoon_end ?: '17:30'), 0, 5),
        );
    }

    public function containsOpeningPeriod(mixed $startAt, mixed $endAt): bool
    {
        $start = $startAt instanceof Carbon ? $startAt : Carbon::parse($startAt);
        $end = $endAt instanceof Carbon ? $endAt : Carbon::parse($endAt);
        $startMinute = ($start->hour * 60) + $start->minute;
        $endMinute = ($end->hour * 60) + $end->minute;
        $morningStart = static::minutes($this->morning_start, '07:30');
        $morningEnd = static::minutes($this->morning_end, '12:00');
        $afternoonStart = static::minutes($this->afternoon_start, '13:30');
        $afternoonEnd = static::minutes($this->afternoon_end, '17:30');

        $startAllowed = ($startMinute >= $morningStart && $startMinute < $morningEnd)
            || ($startMinute >= $afternoonStart && $startMinute < $afternoonEnd);
        $endAllowed = ($endMinute > $morningStart && $endMinute <= $morningEnd)
            || ($endMinute > $afternoonStart && $endMinute <= $afternoonEnd);

        return $startAllowed && $endAllowed && $startMinute >= $morningStart && $endMinute <= $afternoonEnd;
    }

    private static function minutes(?string $time, string $default): int
    {
        $time = substr((string) ($time ?: $default), 0, 5);
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }
}
