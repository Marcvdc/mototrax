<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'content' => fake()->paragraph(),
            'type' => fake()->randomElement(['text', 'route_share', 'maintenance']),
            'route_id' => fake()->optional(0.3)->randomElement(\App\Models\Route::pluck('id')->toArray()),
            'maintenance_log_id' => fake()->optional(0.3)->randomElement(\App\Models\MaintenanceLog::pluck('id')->toArray()),
            'likes_count' => fake()->numberBetween(0, 50),
            'comments_count' => fake()->numberBetween(0, 20),
        ];
    }
}
