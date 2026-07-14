<?php

namespace Modules\CrmPages\Filament\Resources\CrmPages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmPages\Filament\Resources\CrmPages\CrmPageResource;

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
