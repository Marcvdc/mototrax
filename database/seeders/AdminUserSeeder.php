<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@mototrax.dev',
            'password' => 'password', // Laravel will automatically hash this due to the 'hashed' cast
        ]);
    }
}
