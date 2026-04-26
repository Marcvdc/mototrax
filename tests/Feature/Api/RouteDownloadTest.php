<?php

namespace Tests\Feature\Api;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_public_route_can_be_downloaded_by_anonymous(): void
    {
        $route = $this->createRouteWithGpx(isPublic: true);

        $response = $this->get("/api/routes/{$route->id}/gpx");

        $response->assertOk();
        $this->assertSame('application/gpx+xml', $response->headers->get('content-type'));
    }

    public function test_private_route_download_is_forbidden_for_non_owner(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->get("/api/routes/{$route->id}/gpx")
            ->assertStatus(403);
    }

    public function test_private_route_download_works_for_owner(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);

        $this->actingAs($route->user, 'sanctum')
            ->get("/api/routes/{$route->id}/gpx")
            ->assertOk();
    }

    public function test_returns_404_when_file_missing(): void
    {
        $route = $this->createRouteWithGpx(isPublic: true);
        Storage::disk(RouteService::DISK)->delete($route->gpx_file);

        $this->getJson("/api/routes/{$route->id}/gpx")->assertStatus(404);
    }

    public function test_destroy_removes_gpx_file_from_storage(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);
        Storage::disk(RouteService::DISK)->assertExists($route->gpx_file);

        $this->actingAs($route->user, 'sanctum')
            ->deleteJson("/api/routes/{$route->id}")
            ->assertOk();

        Storage::disk(RouteService::DISK)->assertMissing($route->gpx_file);
        $this->assertDatabaseMissing('routes', ['id' => $route->id]);
    }

    public function test_update_by_non_owner_is_forbidden(): void
    {
        $route = $this->createRouteWithGpx(isPublic: true);
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/routes/{$route->id}", ['name' => 'hacked'])
            ->assertStatus(403);
    }

    private function createRouteWithGpx(bool $isPublic): Route
    {
        $user = User::factory()->create();
        $service = app(RouteService::class);

        return $service->createFromUpload(
            $user,
            new UploadedFile(
                path: base_path('tests/Fixtures/gpx/sample-track.gpx'),
                originalName: 'sample-track.gpx',
                mimeType: 'application/gpx+xml',
                error: null,
                test: true,
            ),
            ['name' => 'Download rit', 'is_public' => $isPublic],
        );
    }
}
