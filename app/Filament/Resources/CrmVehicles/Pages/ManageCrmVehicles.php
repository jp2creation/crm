<?php

namespace App\Filament\Resources\CrmVehicles\Pages;

use App\Filament\Resources\CrmVehicles\CrmVehicleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrmVehicles extends ManageRecords
{
    protected static string $resource = CrmVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
