<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CrmCore\Services\UploadedCrmFileCleaner;
use Modules\CrmCore\Support\CrmReferenceCache;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $bio
 * @property string|null $photo_url
 * @property string $role
 * @property bool $active
 * @property-read User|null $account
 * @property-read Collection<int, CrmSite> $sites
 * @property-read Collection<int, CrmModule> $modules
 * @property-read Collection<int, CrmPermission> $permissions
 * @property-read Collection<int, CrmUserSiteModulePermission> $siteModulePermissions
 * @property-read Collection<int, CrmReservation> $reservations
 * @property-read Collection<int, CrmEquipmentRental> $equipmentRentals
 * @property-read Collection<int, CrmCashRegisterDay> $createdCashRegisterDays
 * @property-read Collection<int, CrmCashMovement> $cashMovementsUploads
 */
class CrmUser extends Model
{
    protected $table = 'crm_users';

    protected $fillable = [
        'user_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
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

            if ($user->wasChanged('photo_url')) {
                app(UploadedCrmFileCleaner::class)->deletePublicUpload($user->getOriginal('photo_url'));
            }

            CrmReferenceCache::forgetUsers();
        });

        static::deleting(function (CrmUser $user): void {
            $user->sites()->detach();
            $user->modules()->detach();
            $user->permissions()->detach();
            $user->siteModulePermissions()->delete();
        });

        static::deleted(function (CrmUser $user): void {
            app(UploadedCrmFileCleaner::class)->deletePublicUpload($user->getAttribute('photo_url'));
            CrmReferenceCache::forgetUsers();
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<CrmSite, $this>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(CrmSite::class, 'crm_user_sites', 'user_id', 'site_id')
            ->withPivot('is_default');
    }

    /**
     * @return BelongsToMany<CrmModule, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(CrmModule::class, 'crm_user_modules', 'user_id', 'module_id');
    }

    /**
     * @return BelongsToMany<CrmPermission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(CrmPermission::class, 'crm_user_permissions', 'user_id', 'permission_id');
    }

    /**
     * @return HasMany<CrmUserSiteModulePermission, $this>
     */
    public function siteModulePermissions(): HasMany
    {
        return $this->hasMany(CrmUserSiteModulePermission::class, 'user_id');
    }

    /**
     * @return HasMany<CrmReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(CrmReservation::class, 'user_id');
    }

    /**
     * @return HasMany<CrmEquipmentRental, $this>
     */
    public function equipmentRentals(): HasMany
    {
        return $this->hasMany(CrmEquipmentRental::class, 'user_id');
    }

    /**
     * @return HasMany<CrmCashRegisterDay, $this>
     */
    public function createdCashRegisterDays(): HasMany
    {
        return $this->hasMany(CrmCashRegisterDay::class, 'created_by');
    }

    /**
     * @return HasMany<CrmCashMovement, $this>
     */
    public function cashMovementsUploads(): HasMany
    {
        return $this->hasMany(CrmCashMovement::class, 'uploaded_by');
    }
}
