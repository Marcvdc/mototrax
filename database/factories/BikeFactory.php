<?php

namespace Database\Factories;

use App\Models\Bike;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bike>
 */
class BikeFactory extends Factory
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
            'brand' => fake()->randomElement(['Yamaha', 'Honda', 'Kawasaki', 'Suzuki', 'Ducati', 'BMW', 'KTM', 'Harley-Davidson']),
            'model' => fake()->words(2, true),
            'year' => fake()->numberBetween(2015, 2024),
            'km_current' => fake()->numberBetween(1000, 50000),
            'description' => fake()->sentence(),
        ];
    }
}
