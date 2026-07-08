<?php

namespace App\Filament\Resources\CrmPermissions\Pages;

use App\Filament\Resources\CrmPermissions\CrmPermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrmPermissions extends ManageRecords
{
    protected static string $resource = CrmPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
