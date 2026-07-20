<?php

namespace App\Policies;

use App\Models\User;

class FilamentAdminPolicy
{
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
        return $user->canUsePlatformAdministration();
    }
}
