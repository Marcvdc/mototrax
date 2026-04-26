<?php

namespace Tests\Feature\Api;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RouteService::DISK);
    }

    public function test_unauthenticated_upload_is_rejected(): void
    {
        $this->postJson('/api/routes', [])->assertStatus(401);
    }

    public function test_authenticated_upload_persists_route_and_parses_metadata(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/routes', [
            'gpx_file' => $this->fixtureUpload('sample-track.gpx'),
            'name' => 'Mijn rondje',
            'is_public' => true,
            'tags' => ['scenic'],
            'difficulty' => 'easy',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Mijn rondje')
            ->assertJsonPath('data.is_public', true)
            ->assertJsonPath('data.waypoint_count', 3);

        $this->assertDatabaseCount('routes', 1);
        $route = Route::query()->firstOrFail();
        Storage::disk(RouteService::DISK)->assertExists($route->gpx_file);
    }

    public function test_upload_without_gpx_file_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/routes', [
            'name' => 'No file',
        ])->assertStatus(422)->assertJsonValidationErrors('gpx_file');
    }

    public function test_upload_with_wrong_mime_returns_422(): void
    {
        $user = User::factory()->create();

        $bogus = UploadedFile::fake()->create('not-gpx.png', 10, 'image/png');

        $this->actingAs($user, 'sanctum')->postJson('/api/routes', [
            'gpx_file' => $bogus,
        ])->assertStatus(422)->assertJsonValidationErrors('gpx_file');
    }

    public function test_upload_with_invalid_gpx_content_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/routes', [
            'gpx_file' => $this->fixtureUpload('empty.gpx'),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Invalid GPX file.');
    }

    public function test_upload_with_unknown_difficulty_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/routes', [
            'gpx_file' => $this->fixtureUpload('sample-track.gpx'),
            'difficulty' => 'extreme',
        ])->assertStatus(422)->assertJsonValidationErrors('difficulty');
    }

    private function fixtureUpload(string $name): UploadedFile
    {
        return new UploadedFile(
            path: base_path("tests/Fixtures/gpx/{$name}"),
            originalName: $name,
            mimeType: 'application/gpx+xml',
            error: null,
            test: true,
        );
    }
}
