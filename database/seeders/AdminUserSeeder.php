<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@mototrax.dev']);
        $admin->name = 'Admin User';
        $admin->password = 'password';
        $admin->is_admin = true;
        $admin->save();
    }
}
