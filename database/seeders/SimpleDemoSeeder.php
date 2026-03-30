<?php

namespace Database\Seeders;

use App\Models\Bike;
use App\Models\MaintenanceLog;
use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpleDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users
        $users = User::factory(3)->create();

        foreach ($users as $user) {
            // Create 1-2 bikes per user
            $bikes = Bike::factory(rand(1, 2))->create(['user_id' => $user->id]);
            
            // Create maintenance logs for bikes
            foreach ($bikes as $bike) {
                MaintenanceLog::factory(rand(1, 3))->create([
                    'user_id' => $user->id,
                    'bike_id' => $bike->id,
                ]);
            }
            
            // Create routes
            $routes = Route::factory(rand(1, 2))->create(['user_id' => $user->id]);
            
            // Create posts
            Post::factory(rand(2, 4))->create(['user_id' => $user->id]);
        }

        // Create some specific route share posts
        $routes = Route::all();
        foreach ($routes->take(3) as $route) {
            Post::create([
                'user_id' => $route->user_id,
                'content' => 'Just completed this amazing route! Highly recommended for weekend rides.',
                'type' => 'route_share',
                'route_id' => $route->id,
                'likes_count' => rand(5, 15),
                'comments_count' => rand(2, 8),
            ]);
        }

        // Create some maintenance posts
        $maintenanceLogs = MaintenanceLog::all();
        foreach ($maintenanceLogs->take(2) as $log) {
            Post::create([
                'user_id' => $log->user_id,
                'content' => 'Just finished some maintenance work on the bike. Running smooth now!',
                'type' => 'maintenance',
                'maintenance_log_id' => $log->id,
                'likes_count' => rand(3, 10),
                'comments_count' => rand(1, 5),
            ]);
        }
    }
}
