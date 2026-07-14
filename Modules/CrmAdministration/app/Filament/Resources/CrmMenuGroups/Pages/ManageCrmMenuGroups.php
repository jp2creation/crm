<?php

namespace Modules\CrmAdministration\Filament\Resources\CrmMenuGroups\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmAdministration\Filament\Resources\CrmMenuGroups\CrmMenuGroupResource;

class ManageCrmMenuGroups extends ManageRecords
{
    protected static string $resource = CrmMenuGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
