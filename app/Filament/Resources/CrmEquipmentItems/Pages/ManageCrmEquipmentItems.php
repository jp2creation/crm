<?php

namespace App\Filament\Resources\CrmEquipmentItems\Pages;

use App\Filament\Resources\CrmEquipmentItems\CrmEquipmentItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

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
