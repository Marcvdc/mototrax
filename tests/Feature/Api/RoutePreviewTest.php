<?php

namespace Tests\Feature\Api;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoutePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_public_route_show_returns_geojson_to_anonymous_user(): void
    {
        $route = $this->createRouteWithGpx(isPublic: true);

        $this->getJson("/api/routes/{$route->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $route->id)
            ->assertJsonPath('track.type', 'Feature')
            ->assertJsonPath('track.geometry.type', 'LineString')
            ->assertJsonPath('track.properties.simplified', false);
    }

    public function test_private_route_show_is_forbidden_for_non_owner(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/routes/{$route->id}")
            ->assertStatus(403);
    }

    public function test_private_route_show_succeeds_for_owner(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);

        $this->actingAs($route->user, 'sanctum')
            ->getJson("/api/routes/{$route->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $route->id);
    }

    public function test_private_route_show_is_forbidden_for_anonymous(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);

        $this->getJson("/api/routes/{$route->id}")->assertStatus(403);
    }

    public function test_unknown_route_returns_404(): void
    {
        $this->getJson('/api/routes/9999')->assertStatus(404);
    }

    public function test_index_returns_only_public_routes_for_anonymous(): void
    {
        $publicOne = Route::factory()->public()->create();
        $publicTwo = Route::factory()->public()->create();
        $privateRoute = Route::factory()->create(['is_public' => false]);

        $response = $this->getJson('/api/routes')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($publicOne->id, $ids);
        $this->assertContains($publicTwo->id, $ids);
        $this->assertNotContains($privateRoute->id, $ids);
    }

    public function test_index_returns_public_plus_own_private_for_authenticated(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $publicOther = Route::factory()->public()->create(['user_id' => $stranger->id]);
        $privateOwn = Route::factory()->create(['user_id' => $owner->id, 'is_public' => false]);
        $privateOther = Route::factory()->create(['user_id' => $stranger->id, 'is_public' => false]);

        $ids = collect(
            $this->actingAs($owner, 'sanctum')->getJson('/api/routes')->assertOk()->json('data')
        )->pluck('id')->all();

        $this->assertContains($publicOther->id, $ids);
        $this->assertContains($privateOwn->id, $ids);
        $this->assertNotContains($privateOther->id, $ids);
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
            ['name' => 'Test rit', 'is_public' => $isPublic],
        );
    }
}
