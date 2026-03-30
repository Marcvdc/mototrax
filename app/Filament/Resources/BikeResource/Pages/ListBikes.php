<?php

namespace App\Filament\Resources\BikeResource\Pages;

use App\Filament\Resources\BikeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBikes extends ListRecords
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
