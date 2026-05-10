<?php

namespace Tests\Feature\Filament;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteMapPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_filament_view_page_renders_map_payload(): void
    {
        $admin = User::factory()->create();
        $route = $this->createRouteWithGpx($admin, isPublic: false);

        $response = $this->actingAs($admin)
            ->get("/admin/routes/{$route->id}")
            ->assertOk();

        $response->assertSee('data-route-map', escape: false);
        $response->assertSee('LineString', escape: false);
    }

    public function test_filament_edit_page_renders_map_payload(): void
    {
        $admin = User::factory()->create();
        $route = $this->createRouteWithGpx($admin, isPublic: false);

        $response = $this->actingAs($admin)
            ->get("/admin/routes/{$route->id}/edit")
            ->assertOk();

        $response->assertSee('data-route-map', escape: false);
        $response->assertSee('LineString', escape: false);
    }

    public function test_filament_create_page_does_not_render_map(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get('/admin/routes/create')
            ->assertOk();

        $response->assertDontSee('data-route-map', escape: false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRouteWithGpx(User $owner, bool $isPublic, array $attributes = []): Route
    {
        $service = app(RouteService::class);

        return $service->createFromUpload(
            $owner,
            new UploadedFile(
                path: base_path('tests/Fixtures/gpx/sample-track.gpx'),
                originalName: 'sample-track.gpx',
                mimeType: 'application/gpx+xml',
                error: null,
                test: true,
            ),
            array_merge(['name' => 'Test rit', 'is_public' => $isPublic], $attributes),
        );
    }
}
