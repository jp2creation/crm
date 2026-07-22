<?php

namespace Modules\CrmStats\Filament\Concerns;

use App\Models\User;

trait AuthorizesStatsAccess
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->canUsePlatformAdministration() || $user->can('view_stats'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canView(): bool
    {
        return static::canAccess();
    }
}
