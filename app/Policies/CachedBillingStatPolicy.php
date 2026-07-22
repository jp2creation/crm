<?php

namespace App\Policies;

use App\Models\CachedBillingStat;
use App\Models\User;

class CachedBillingStatPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewStats($user);
    }

    public function view(User $user, CachedBillingStat $cachedBillingStat): bool
    {
        return $this->canViewStats($user);
    }

    public function create(User $user): bool
    {
        return $user->canUsePlatformAdministration() || $user->can('manage_stats');
    }

    public function update(User $user, CachedBillingStat $cachedBillingStat): bool
    {
        return $user->canUsePlatformAdministration() || $user->can('manage_stats');
    }

    public function delete(User $user, CachedBillingStat $cachedBillingStat): bool
    {
        return $user->canUsePlatformAdministration() || $user->can('manage_stats');
    }

    public function deleteAny(User $user): bool
    {
        return $user->canUsePlatformAdministration() || $user->can('manage_stats');
    }

    private function canViewStats(User $user): bool
    {
        return $user->canUsePlatformAdministration() || $user->can('view_stats');
    }
}
