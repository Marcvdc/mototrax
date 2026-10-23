<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Services\Gpx\GpxParser;
use App\Services\Gpx\InvalidGpxException;
use App\Services\RouteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillRouteWaypoints extends Command
{
    protected $signature = 'routes:backfill-waypoints {--force : Ook routes die de pins al hebben opnieuw berekenen}';

    protected $description = 'Vult map_waypoints voor routes die van vóór de Google Maps-deeplink dateren';

    public function __construct(
        private readonly GpxParser $parser,
        private readonly RouteService $routeService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Route::query();

        if (! $this->option('force')) {
            $query->whereNull('map_waypoints');
        }

        $routes = $query->get();

        if ($routes->isEmpty()) {
            $this->info('Niets te doen, alle routes hebben hun pins al.');

            return self::SUCCESS;
        }

        $filled = 0;
        $skipped = 0;

        foreach ($routes as $route) {
            $waypoints = $this->waypointsFor($route);

            if ($waypoints === null) {
                $skipped++;

                continue;
            }

            $route->update(['map_waypoints' => $waypoints]);
            $filled++;
        }

        $this->info("Bijgewerkt: {$filled} route(s).");

        if ($skipped > 0) {
            $this->warn("Overgeslagen: {$skipped} route(s) zonder bruikbaar GPX-bestand.");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{lat: float, lng: float}>|null
     */
    private function waypointsFor(Route $route): ?array
    {
        $disk = Storage::disk(RouteService::DISK);

        if (! is_string($route->gpx_file) || $route->gpx_file === '' || ! $disk->exists($route->gpx_file)) {
            $this->line("  Route #{$route->id}: GPX-bestand ontbreekt, overgeslagen.");

            return null;
        }

        try {
            $parsed = $this->parser->parseFile($disk->path($route->gpx_file));
        } catch (InvalidGpxException $e) {
            $this->line("  Route #{$route->id}: ongeldige GPX ({$e->getMessage()}), overgeslagen.");

            return null;
        }

        return $this->routeService->mapWaypointsFor($parsed);
    }
}
