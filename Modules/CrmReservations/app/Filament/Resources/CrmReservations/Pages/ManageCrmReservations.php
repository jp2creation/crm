<?php

namespace Modules\CrmReservations\Filament\Resources\CrmReservations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\CrmReservations\Filament\Resources\CrmReservations\CrmReservationResource;

class ManageCrmReservations extends ManageRecords
{
    protected static string $resource = CrmReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
