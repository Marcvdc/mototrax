<?php

namespace Database\Factories;

use App\Models\Route;
use App\Models\User;
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
            'user_id' => User::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'gpx_file' => 'gpx/'.fake()->uuid().'.gpx',
            'distance' => fake()->randomFloat(3, 10, 200),
            'estimated_time' => fake()->numberBetween(30, 240),
            'difficulty' => fake()->randomElement(array_keys(Route::getDifficultyLevels())),
            'tags' => fake()->randomElements(array_keys(Route::getCommonTags()), rand(1, 3)),
            'is_public' => false,
            'waypoint_count' => fake()->numberBetween(50, 5000),
        ];
    }

    public function public(): self
    {
        return $this->state(fn (): array => ['is_public' => true]);
    }
}
