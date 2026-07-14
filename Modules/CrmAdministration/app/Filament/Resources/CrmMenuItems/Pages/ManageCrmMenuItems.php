<?php

namespace Modules\CrmAdministration\Filament\Resources\CrmMenuItems\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmAdministration\Filament\Resources\CrmMenuItems\CrmMenuItemResource;

class ManageCrmMenuItems extends ManageRecords
{
    protected static string $resource = CrmMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
