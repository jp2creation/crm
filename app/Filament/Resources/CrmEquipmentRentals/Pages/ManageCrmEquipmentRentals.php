<?php

namespace App\Filament\Resources\CrmEquipmentRentals\Pages;

use App\Filament\Resources\CrmEquipmentRentals\CrmEquipmentRentalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrmEquipmentRentals extends ManageRecords
{
    protected static string $resource = CrmEquipmentRentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
