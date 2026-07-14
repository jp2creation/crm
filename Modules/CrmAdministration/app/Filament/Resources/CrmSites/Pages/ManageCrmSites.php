<?php

namespace Modules\CrmAdministration\Filament\Resources\CrmSites\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmAdministration\Filament\Resources\CrmSites\CrmSiteResource;

class ManageCrmSites extends ManageRecords
{
    protected static string $resource = CrmSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
