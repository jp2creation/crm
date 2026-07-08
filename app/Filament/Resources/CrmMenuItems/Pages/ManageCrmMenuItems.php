<?php

namespace App\Filament\Resources\CrmMenuItems\Pages;

use App\Filament\Resources\CrmMenuItems\CrmMenuItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrmMenuItems extends ManageRecords
{
    protected static string $resource = CrmMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
