<?php

namespace Tests\Feature\Seeders;

use App\Models\Bike;
use App\Models\MaintenanceLog;
use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_produces_the_expected_demo_dataset(): void
    {
        Storage::fake(RouteService::DISK);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, User::query()->count());
        $this->assertSame(9, Bike::query()->count());
        $this->assertSame(27, MaintenanceLog::query()->count());
        $this->assertSame(10, Route::query()->count());
        $this->assertSame(9, Route::query()->public()->count());
        $this->assertSame(20, Post::query()->count());

        $this->assertDatabaseHas('users', [
            'email' => 'admin@mototrax.dev',
            'is_admin' => true,
        ]);
    }

    public function test_every_seeded_route_has_a_real_gpx_file_on_disk(): void
    {
        Storage::fake(RouteService::DISK);

        $this->seed(DatabaseSeeder::class);

        $disk = Storage::disk(RouteService::DISK);

        foreach (Route::query()->get() as $route) {
            $this->assertNotEmpty($route->gpx_file);
            $this->assertTrue(
                $disk->exists($route->gpx_file),
                "GPX-bestand ontbreekt voor route {$route->name}: {$route->gpx_file}",
            );
        }
    }
}
