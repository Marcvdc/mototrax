<?php

namespace Database\Factories;

use App\Models\MaintenanceLog;
use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'type' => 'text',
            'route_id' => null,
            'maintenance_log_id' => null,
            'likes_count' => 0,
            'comments_count' => 0,
        ];
    }

    public function text(): static
    {
        return $this->state(fn (): array => [
            'type' => 'text',
            'route_id' => null,
            'maintenance_log_id' => null,
        ]);
    }

    public function routeShare(?Route $route = null): static
    {
        return $this->state(fn (): array => [
            'type' => 'route_share',
            'route_id' => $route?->id ?? Route::factory(),
            'maintenance_log_id' => null,
        ]);
    }

    public function maintenance(?MaintenanceLog $log = null): static
    {
        return $this->state(fn (): array => [
            'type' => 'maintenance',
            'maintenance_log_id' => $log?->id ?? MaintenanceLog::factory(),
            'route_id' => null,
        ]);
    }
}
