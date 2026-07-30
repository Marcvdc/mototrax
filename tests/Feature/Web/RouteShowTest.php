<?php

namespace Tests\Feature\Web;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_public_route_renders_map_payload_for_anonymous_visitor(): void
    {
        $route = $this->createRouteWithGpx(isPublic: true);

        $response = $this->get("/routes/{$route->id}")->assertOk();

        $response->assertSee('data-route-map', escape: false);
        $response->assertSee('LineString', escape: false);
        $response->assertSee($route->name, escape: true);
    }

    public function test_private_route_returns_404_to_anonymous_visitor(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);

        $this->get("/routes/{$route->id}")->assertNotFound();
    }

    public function test_private_route_returns_404_even_for_owner_on_public_page(): void
    {
        $route = $this->createRouteWithGpx(isPublic: false);

        $this->actingAs($route->user)
            ->get("/routes/{$route->id}")
            ->assertNotFound();
    }

    public function test_unknown_route_returns_404(): void
    {
        $this->get('/routes/9999')->assertNotFound();
    }

    public function test_response_includes_map_csp_and_security_headers(): void
    {
        $route = $this->createRouteWithGpx(isPublic: true);

        $response = $this->get("/routes/{$route->id}")->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('tile.openstreetmap.org', $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);

        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_user_supplied_html_in_name_is_escaped(): void
    {
        $route = $this->createRouteWithGpx(
            isPublic: true,
            attributes: ['name' => '<script>alert(1)</script>'],
        );

        $response = $this->get("/routes/{$route->id}")->assertOk();

        $response->assertDontSee('<script>alert(1)</script>', escape: false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRouteWithGpx(bool $isPublic, array $attributes = []): Route
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
            array_merge(['name' => 'Test rit', 'is_public' => $isPublic], $attributes),
        );
    }
}
