<?php

namespace App\Filament\Resources\CrmEquipmentCategories\Pages;

use App\Filament\Resources\CrmEquipmentCategories\CrmEquipmentCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

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
