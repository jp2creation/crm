<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CrmVehicle extends Model
{
    protected $table = 'crm_vehicles';

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'color',
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
        static::deleting(function (CrmVehicle $vehicle): void {
            if ($vehicle->reservations()->exists()) {
                throw ValidationException::withMessages([
                    'vehicle' => 'Ce vehicule a des reservations. Desactive-le pour le masquer.',
                ]);
            }
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
