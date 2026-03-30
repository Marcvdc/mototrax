<?php

namespace Database\Seeders;

use App\Models\Bike;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpleBikeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        
        if ($user) {
            Bike::create([
                'user_id' => $user->id,
                'brand' => 'Yamaha',
                'model' => 'MT-07',
                'year' => 2023,
                'km_current' => 5000,
                'description' => 'Great bike for city riding',
            ]);
            
            Bike::create([
                'user_id' => $user->id,
                'brand' => 'Honda',
                'model' => 'CBR600RR',
                'year' => 2022,
                'km_current' => 8500,
                'description' => 'Sport bike for track days',
            ]);
        }
    }
}
