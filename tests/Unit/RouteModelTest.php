<?php

namespace Tests\Unit;

use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_scope_returns_only_public_routes(): void
    {
        Route::factory()->count(2)->create(['is_public' => false]);
        Route::factory()->public()->count(3)->create();

        $this->assertCount(3, Route::query()->public()->get());
    }

    public function test_is_public_is_cast_to_boolean(): void
    {
        $route = Route::factory()->public()->create();

        $this->assertIsBool($route->fresh()->is_public);
        $this->assertTrue($route->fresh()->is_public);
    }

    public function test_bbox_is_cast_to_array(): void
    {
        $route = Route::factory()->create([
            'bbox' => ['min_lat' => 50.0, 'min_lng' => 4.0, 'max_lat' => 51.0, 'max_lng' => 5.0],
        ]);

        $this->assertIsArray($route->fresh()->bbox);
        $this->assertEqualsWithDelta(50.0, $route->fresh()->bbox['min_lat'], 0.0001);
    }
}
