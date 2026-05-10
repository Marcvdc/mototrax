<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mototrax.dev'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'is_admin' => true,
            ],
        );
    }
}
