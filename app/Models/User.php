<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @var array<int, string>
     */
    public const PLATFORM_ADMIN_ROLES = ['admin', 'Admin', 'Super Admin'];

    /**
     * @var array<int, string>
     */
    public const PLATFORM_ADMIN_PERMISSIONS = ['filament.access', 'filament.manage'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->canUsePlatformAdministration();
    }

    public function canUsePlatformAdministration(): bool
    {
        if ($this->hasAnyRole(self::PLATFORM_ADMIN_ROLES)) {
            return true;
        }

        return $this->getAllPermissions()
            ->contains(fn ($permission): bool => in_array($permission->name, self::PLATFORM_ADMIN_PERMISSIONS, true));
    }

    public function crmUser(): HasOne
    {
        return $this->hasOne(CrmUser::class, 'user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
