<?php

namespace App\Filament\Resources\CrmPages\Pages;

use App\Filament\Resources\CrmPages\CrmPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCrmPages extends ManageRecords
{
    protected static string $resource = CrmPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
