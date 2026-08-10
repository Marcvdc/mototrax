<?php

namespace Tests\Feature\Services;

use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RouteServiceStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_and_persists_no_route_when_gpx_storage_fails(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('putFile')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with(RouteService::DISK)->andReturn($disk);

        $user = User::factory()->create();
        $upload = new UploadedFile(
            path: base_path('tests/Fixtures/gpx/sample-track.gpx'),
            originalName: 'sample-track.gpx',
            mimeType: 'application/gpx+xml',
            error: null,
            test: true,
        );

        try {
            app(RouteService::class)->createFromUpload($user, $upload, ['name' => 'Mislukte upload']);
            $this->fail('Verwachtte een RuntimeException bij een gefaalde opslag.');
        } catch (RuntimeException) {
            // verwacht
        }

        $this->assertSame(0, Route::query()->count());
    }
}
