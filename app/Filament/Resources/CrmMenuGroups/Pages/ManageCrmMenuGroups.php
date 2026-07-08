<?php

namespace App\Filament\Resources\CrmMenuGroups\Pages;

use App\Filament\Resources\CrmMenuGroups\CrmMenuGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrmMenuGroups extends ManageRecords
{
    protected static string $resource = CrmMenuGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
