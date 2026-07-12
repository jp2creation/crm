<?php

namespace App\Policies;

use App\Models\User;

class FilamentAdminPolicy
{
    /**
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Admin', 'Super Admin'];

    /**
     * @var array<int, string>
     */
    private const OVERRIDE_PERMISSIONS = ['filament.access', 'filament.manage'];

    public function viewAny(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function view(User $user, mixed $record): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function update(User $user, mixed $record): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function delete(User $user, mixed $record): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function restore(User $user, mixed $record): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function replicate(User $user, mixed $record): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function reorder(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function export(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    public function import(User $user): bool
    {
        return $this->canUseFilamentAdmin($user);
    }

    private function canUseFilamentAdmin(User $user): bool
    {
        if ($user->hasAnyRole(self::ADMIN_ROLES)) {
            return true;
        }

        return $user->getAllPermissions()
            ->contains(fn ($permission): bool => in_array($permission->name, self::OVERRIDE_PERMISSIONS, true));
    }
}
