<?php

namespace App\Filament\Resources\CrmModules\Pages;

use App\Filament\Resources\CrmModules\CrmModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

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
