<?php

namespace Modules\CrmReservations\Filament\Resources\CrmVehicles\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmReservations\Filament\Resources\CrmVehicles\CrmVehicleResource;

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
