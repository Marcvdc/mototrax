<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use App\Filament\Resources\RouteResource\Concerns\FillsGpxMetadata;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoute extends EditRecord
{
    use FillsGpxMetadata;

    protected static string $resource = RouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $original = $this->record->getOriginal('gpx_file');

        if (($data['gpx_file'] ?? null) === $original) {
            return $data;
        }

        return $this->fillGpxMetadata($data);
    }
}
