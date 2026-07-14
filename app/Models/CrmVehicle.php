<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
