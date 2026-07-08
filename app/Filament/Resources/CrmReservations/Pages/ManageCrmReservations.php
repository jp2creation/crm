<?php

namespace App\Filament\Resources\CrmReservations\Pages;

use App\Filament\Resources\CrmReservations\CrmReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

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
