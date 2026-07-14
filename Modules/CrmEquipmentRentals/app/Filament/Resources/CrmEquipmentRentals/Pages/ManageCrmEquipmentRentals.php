<?php

namespace Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentRentals\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentRentals\CrmEquipmentRentalResource;

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
