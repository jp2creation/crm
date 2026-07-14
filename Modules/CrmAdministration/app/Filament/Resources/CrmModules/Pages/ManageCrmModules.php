<?php

namespace Modules\CrmAdministration\Filament\Resources\CrmModules\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmAdministration\Filament\Resources\CrmModules\CrmModuleResource;

class ManageCrmModules extends ManageRecords
{
    protected static string $resource = CrmModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
