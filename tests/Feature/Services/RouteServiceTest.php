<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\Gpx\GpxParser;
use App\Services\Gpx\LineSimplifier;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteServiceTest extends TestCase
{
    use RefreshDatabase;

    private RouteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
        $this->service = new RouteService(new GpxParser, new LineSimplifier);
    }

    public function test_create_from_upload_persists_route_with_parsed_metadata(): void
    {
        $user = User::factory()->create();
        $upload = $this->fixtureUpload('sample-track.gpx');

        $route = $this->service->createFromUpload($user, $upload, [
            'name' => 'Mijn rit',
            'description' => 'Test',
            'tags' => ['scenic'],
            'difficulty' => 'easy',
            'is_public' => true,
        ]);

        $this->assertSame($user->id, $route->user_id);
        $this->assertSame('Mijn rit', $route->name);
        $this->assertTrue($route->is_public);
        $this->assertSame(15, $route->estimated_time);
        $this->assertGreaterThan(0, (float) $route->distance);
        $this->assertSame(3, $route->waypoint_count);
        $this->assertSame(51.4416, (float) $route->start_lat);
        Storage::disk(RouteService::DISK)->assertExists($route->gpx_file);
    }

    public function test_falls_back_to_average_speed_when_gpx_has_no_timestamps(): void
    {
        $user = User::factory()->create();
        $upload = $this->fixtureUpload('no-time.gpx');

        $route = $this->service->createFromUpload($user, $upload, ['name' => 'NoTime']);

        // 1 deg lng @ equator ≈ 111.32 km / 60 km/u * 60 ≈ 111 minutes.
        $this->assertEqualsWithDelta(111, $route->estimated_time, 2);
    }

    public function test_uses_track_name_from_gpx_when_attribute_name_missing(): void
    {
        $user = User::factory()->create();
        $upload = $this->fixtureUpload('sample-track.gpx');

        $route = $this->service->createFromUpload($user, $upload, []);

        $this->assertSame('Eindhoven Loop', $route->name);
    }

    public function test_to_geojson_returns_linestring_with_route_coordinates(): void
    {
        $user = User::factory()->create();
        $upload = $this->fixtureUpload('sample-track.gpx');
        $route = $this->service->createFromUpload($user, $upload, []);

        $geojson = $this->service->toGeoJson($route);

        $this->assertSame('Feature', $geojson['type']);
        $this->assertSame('LineString', $geojson['geometry']['type']);
        $this->assertCount(3, $geojson['geometry']['coordinates']);
        $this->assertEqualsWithDelta(5.4697, $geojson['geometry']['coordinates'][0][0], 0.0001);
        $this->assertEqualsWithDelta(51.4416, $geojson['geometry']['coordinates'][0][1], 0.0001);
        $this->assertFalse($geojson['properties']['simplified']);
    }

    private function fixtureUpload(string $name): UploadedFile
    {
        $source = base_path("tests/Fixtures/gpx/{$name}");

        return new UploadedFile(
            path: $source,
            originalName: $name,
            mimeType: 'application/gpx+xml',
            error: null,
            test: true,
        );
    }
}
