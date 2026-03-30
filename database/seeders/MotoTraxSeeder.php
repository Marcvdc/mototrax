<?php

namespace Database\Seeders;

use App\Models\Bike;
use App\Models\MaintenanceLog;
use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Seeder;

class MotoTraxSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users
        $users = User::factory(5)->create();

        // Create bikes for each user
        foreach ($users as $user) {
            $bikes = Bike::factory(rand(1, 3))->create(['user_id' => $user->id]);
            
            // Create maintenance logs for each bike
            foreach ($bikes as $bike) {
                MaintenanceLog::factory(rand(2, 8))->create([
                    'user_id' => $user->id,
                    'bike_id' => $bike->id,
                ]);
            }
            
            // Create routes for each user
            $routes = Route::factory(rand(2, 5))->create(['user_id' => $user->id]);
            
            // Create posts for each user
            Post::factory(rand(3, 10))->create(['user_id' => $user->id]);
        }

        // Create some route share posts
        $routes = Route::all();
        foreach ($routes->random(10) as $route) {
            Post::create([
                'user_id' => $route->user_id,
                'content' => 'Just completed this amazing route! Highly recommended for weekend rides.',
                'type' => 'route_share',
                'route_id' => $route->id,
                'likes_count' => rand(0, 20),
                'comments_count' => rand(0, 10),
            ]);
        }

        // Create some maintenance posts
        $maintenanceLogs = MaintenanceLog::all();
        foreach ($maintenanceLogs->random(8) as $log) {
            Post::create([
                'user_id' => $log->user_id,
                'content' => 'Just finished some maintenance work on the bike. Running smooth now!',
                'type' => 'maintenance',
                'maintenance_log_id' => $log->id,
                'likes_count' => rand(0, 15),
                'comments_count' => rand(0, 8),
            ]);
        }
    }
}
