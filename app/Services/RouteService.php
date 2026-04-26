<?php

namespace App\Services;

use App\Models\Route;
use App\Models\User;
use App\Services\Gpx\GpxParser;
use App\Services\Gpx\InvalidGpxException;
use App\Services\Gpx\LineSimplifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RouteService
{
    public const DISK = 'local';

    public const DIRECTORY = 'gpx';

    public const DEFAULT_AVERAGE_SPEED_KMH = 60.0;

    public const SIMPLIFY_THRESHOLD = 2000;

    public const SIMPLIFY_TOLERANCE = 0.0001;

    public function __construct(
        private readonly GpxParser $parser,
        private readonly LineSimplifier $simplifier,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  name, description, tags, difficulty, is_public
     *
     * @throws InvalidGpxException
     */
    public function createFromUpload(User $user, UploadedFile $gpxFile, array $attributes): Route
    {
        $parsed = $this->parser->parseFile($gpxFile->getRealPath());

        return DB::transaction(function () use ($user, $gpxFile, $attributes, $parsed): Route {
            $storedPath = Storage::disk(self::DISK)->putFile(self::DIRECTORY, $gpxFile);

            return Route::query()->create([
                'user_id' => $user->id,
                'name' => $attributes['name'] ?? $parsed->name ?? 'Untitled route',
                'description' => $attributes['description'] ?? null,
                'gpx_file' => $storedPath,
                'tags' => $attributes['tags'] ?? [],
                'difficulty' => $attributes['difficulty'] ?? null,
                'is_public' => (bool) ($attributes['is_public'] ?? false),
                'distance' => $parsed->distanceKm,
                'estimated_time' => $parsed->durationMinutes ?? $this->fallbackDurationMinutes($parsed->distanceKm),
                'bbox' => $parsed->bbox,
                'start_lat' => $parsed->start['lat'],
                'start_lng' => $parsed->start['lng'],
                'end_lat' => $parsed->end['lat'],
                'end_lng' => $parsed->end['lng'],
                'waypoint_count' => $parsed->waypointCount,
            ]);
        });
    }

    /**
     * @return array{type: string, geometry: array{type: string, coordinates: list<array{0: float, 1: float}>}, properties: array<string, mixed>}
     */
    public function toGeoJson(Route $route): array
    {
        $absolute = Storage::disk(self::DISK)->path($route->gpx_file);
        $parsed = $this->parser->parseFile($absolute);

        $points = array_map(
            fn (array $p): array => ['lat' => $p['lat'], 'lng' => $p['lng']],
            $parsed->points,
        );

        if (count($points) > self::SIMPLIFY_THRESHOLD) {
            $points = $this->simplifier->simplify($points, self::SIMPLIFY_TOLERANCE);
        }

        $coordinates = array_map(
            fn (array $p): array => [$p['lng'], $p['lat']],
            $points,
        );

        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
            'properties' => [
                'route_id' => $route->id,
                'name' => $route->name,
                'distance_km' => (float) $route->distance,
                'waypoint_count' => $route->waypoint_count,
                'simplified' => count($points) < count($parsed->points),
            ],
        ];
    }

    private function fallbackDurationMinutes(float $distanceKm): int
    {
        return (int) round(($distanceKm / self::DEFAULT_AVERAGE_SPEED_KMH) * 60);
    }
}
