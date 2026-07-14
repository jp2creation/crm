<?php

namespace Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentCategories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentCategories\CrmEquipmentCategoryResource;

class ManageCrmEquipmentCategories extends ManageRecords
{
    protected static string $resource = CrmEquipmentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
