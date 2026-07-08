<?php

namespace App\Filament\Resources\CrmSites\Pages;

use App\Filament\Resources\CrmSites\CrmSiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

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
