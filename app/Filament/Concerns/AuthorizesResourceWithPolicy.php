<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait AuthorizesResourceWithPolicy
{
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', static::getModel());
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', static::getModel());
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canView(Model $record): bool
    {
        return Gate::allows('view', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return Gate::allows('deleteAny', static::getModel());
    }
}
