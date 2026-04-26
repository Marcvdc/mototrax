<?php

namespace App\Filament\Resources\RouteResource\Concerns;

use App\Services\Gpx\GpxParser;
use App\Services\Gpx\InvalidGpxException;
use App\Services\RouteService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

trait FillsGpxMetadata
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillGpxMetadata(array $data): array
    {
        $path = $data['gpx_file'] ?? null;

        if (! is_string($path) || ! Storage::disk(RouteService::DISK)->exists($path)) {
            return $data;
        }

        try {
            $parsed = app(GpxParser::class)->parseFile(
                Storage::disk(RouteService::DISK)->path($path),
            );
        } catch (InvalidGpxException $e) {
            Notification::make()
                ->title('Ongeldig GPX-bestand')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return $data;
        }

        $data['distance'] = $parsed->distanceKm;
        $data['estimated_time'] = $parsed->durationMinutes
            ?? (int) round(($parsed->distanceKm / RouteService::DEFAULT_AVERAGE_SPEED_KMH) * 60);
        $data['bbox'] = $parsed->bbox;
        $data['start_lat'] = $parsed->start['lat'];
        $data['start_lng'] = $parsed->start['lng'];
        $data['end_lat'] = $parsed->end['lat'];
        $data['end_lng'] = $parsed->end['lng'];
        $data['waypoint_count'] = $parsed->waypointCount;

        if (empty($data['name']) && $parsed->name !== null) {
            $data['name'] = $parsed->name;
        }

        return $data;
    }
}
