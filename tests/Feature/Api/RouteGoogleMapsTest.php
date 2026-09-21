<?php

namespace Tests\Feature\Api;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteGoogleMapsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_show_response_contains_a_google_maps_deeplink(): void
    {
        $route = $this->createRoute();

        $response = $this->getJson(route('api.v1.routes.show', ['route' => $route->id]))->assertOk();

        $url = $response->json('data.google_maps_url');

        $this->assertIsString($url);
        $this->assertStringStartsWith('https://www.google.com/maps/dir/?api=1', $url);
        $this->assertStringContainsString('origin=', $url);
        $this->assertStringContainsString('destination=', $url);
        $this->assertStringContainsString('travelmode=driving', $url);
    }

    public function test_deeplink_is_null_when_the_route_has_no_coordinates(): void
    {
        $route = $this->createRoute();
        $route->forceFill(['start_lat' => null, 'end_lat' => null])->save();

        $this->getJson(route('api.v1.routes.show', ['route' => $route->id]))
            ->assertOk()
            ->assertJsonPath('data.google_maps_url', null);
    }

    public function test_index_exposes_the_deeplink_without_reading_gpx_files(): void
    {
        $this->createRoute();
        $this->createRoute();

        // De deeplink komt uit opgeslagen metadata. Zonder schijf mag de
        // lijst-endpoint dus gewoon blijven werken.
        Storage::fake(RouteService::DISK);

        $response = $this->getJson(route('api.v1.routes.index'))->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertStringStartsWith('https://www.google.com/maps/dir/?api=1', $row['google_maps_url']);
        }
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
            ['name' => 'Publieke rit', 'is_public' => true],
        );
    }
}
