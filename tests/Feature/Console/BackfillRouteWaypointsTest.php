<?php

namespace Tests\Feature\Console;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackfillRouteWaypointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_it_fills_waypoints_for_a_route_that_predates_the_feature(): void
    {
        $route = $this->createRoute();
        $route->forceFill(['map_waypoints' => null])->save();

        $this->artisan('routes:backfill-waypoints')->assertSuccessful();

        $route->refresh();
        $this->assertIsArray($route->map_waypoints);
    }

    public function test_it_skips_routes_that_already_have_waypoints(): void
    {
        $route = $this->createRoute();
        $original = $route->map_waypoints;

        $this->artisan('routes:backfill-waypoints')
            ->expectsOutputToContain('Niets te doen')
            ->assertSuccessful();

        $this->assertSame($original, $route->refresh()->map_waypoints);
    }

    public function test_force_recalculates_routes_that_already_have_waypoints(): void
    {
        $route = $this->createRoute();
        $route->forceFill(['map_waypoints' => [['lat' => 0.0, 'lng' => 0.0]]])->save();

        $this->artisan('routes:backfill-waypoints', ['--force' => true])->assertSuccessful();

        $this->assertNotSame([['lat' => 0.0, 'lng' => 0.0]], $route->refresh()->map_waypoints);
    }

    public function test_it_skips_a_route_whose_gpx_file_is_missing_without_failing(): void
    {
        $route = Route::factory()->create(['gpx_file' => 'gpx/weg.gpx', 'map_waypoints' => null]);

        $this->artisan('routes:backfill-waypoints')
            ->expectsOutputToContain("Route #{$route->id}: GPX-bestand ontbreekt")
            ->assertSuccessful();

        $this->assertNull($route->refresh()->map_waypoints);
    }

    public function test_it_skips_a_route_with_an_invalid_gpx_file_without_failing(): void
    {
        Storage::disk(RouteService::DISK)->put('gpx/kapot.gpx', 'dit is geen gpx');
        $route = Route::factory()->create(['gpx_file' => 'gpx/kapot.gpx', 'map_waypoints' => null]);

        $this->artisan('routes:backfill-waypoints')
            ->expectsOutputToContain("Route #{$route->id}: ongeldige GPX")
            ->assertSuccessful();

        $this->assertNull($route->refresh()->map_waypoints);
    }

    private function createRoute(): Route
    {
        return app(RouteService::class)->createFromUpload(
            User::factory()->create(),
            new UploadedFile(
                path: base_path('tests/Fixtures/gpx/sample-track.gpx'),
                originalName: 'sample-track.gpx',
                mimeType: 'application/gpx+xml',
                error: null,
                test: true,
            ),
            ['name' => 'Oude rit', 'is_public' => true],
        );
    }
}
