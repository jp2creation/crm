<?php

namespace Modules\CrmAdministration\Filament\Resources\CrmPermissions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmAdministration\Filament\Resources\CrmPermissions\CrmPermissionResource;

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
