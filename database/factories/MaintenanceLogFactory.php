<?php

namespace Database\Factories;

use App\Models\Bike;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
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
            'bike_id' => Bike::factory(),
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(['oil_change', 'tire_change', 'brake_service', 'chain_service', 'general_service', 'repair']),
            'description' => fake()->paragraph(),
            'km_at_maintenance' => fake()->numberBetween(1000, 50000),
            'cost' => fake()->randomFloat(2, 50, 500),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
