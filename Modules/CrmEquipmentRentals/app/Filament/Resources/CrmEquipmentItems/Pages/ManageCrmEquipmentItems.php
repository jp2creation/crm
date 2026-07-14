<?php

namespace Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentItems\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentItems\CrmEquipmentItemResource;

class ManageCrmEquipmentItems extends ManageRecords
{
    protected static string $resource = CrmEquipmentItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
