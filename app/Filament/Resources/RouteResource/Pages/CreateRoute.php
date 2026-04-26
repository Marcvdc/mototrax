<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use App\Filament\Resources\RouteResource\Concerns\FillsGpxMetadata;
use Filament\Resources\Pages\CreateRecord;

class CreateRoute extends CreateRecord
{
    use FillsGpxMetadata;

    protected static string $resource = RouteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->fillGpxMetadata($data);
    }
}
