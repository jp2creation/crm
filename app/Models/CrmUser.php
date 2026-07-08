<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmUser extends Model
{
    protected $table = 'crm_users';

    protected $fillable = [
        'user_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'bio',
        'photo_url',
        'role',
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
        static::saved(function (CrmUser $user): void {
            if ($user->wasChanged('name')) {
                $user->reservations()->update(['user_name' => $user->name]);
                $user->equipmentRentals()->update(['user_name' => $user->name]);
            }
        });

        static::deleting(function (CrmUser $user): void {
            $user->sites()->detach();
            $user->modules()->detach();
            $user->permissions()->detach();
        });
    }

    public static function roleOptions(): array
    {
        return [
            'admin' => 'Administrateur',
            'responsable' => 'Responsable site',
            'user' => 'Employe',
            'blocked' => 'Sans acces',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(CrmSite::class, 'crm_user_sites', 'user_id', 'site_id')
            ->withPivot('is_default');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(CrmModule::class, 'crm_user_modules', 'user_id', 'module_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(CrmPermission::class, 'crm_user_permissions', 'user_id', 'permission_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(CrmReservation::class, 'user_id');
    }

    public function equipmentRentals(): HasMany
    {
        return $this->hasMany(CrmEquipmentRental::class, 'user_id');
    }
}
