<?php

namespace App\Filament\Resources\FilamentRoles\Pages;

use App\Filament\Resources\FilamentRoles\FilamentRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFilamentRoles extends ManageRecords
{
    protected static string $resource = FilamentRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
