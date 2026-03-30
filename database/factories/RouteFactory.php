<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
class RouteFactory extends Factory
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
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'distance' => fake()->randomFloat(1, 10, 200),
            'estimated_time' => fake()->numberBetween(30, 240),
            'difficulty' => fake()->randomElement(['easy', 'moderate', 'hard', 'expert']),
            'tags' => fake()->randomElements(['scenic', 'curvy', 'mountain', 'coastal', 'urban', 'rural', 'touring'], rand(1, 3)),
        ];
    }
}
