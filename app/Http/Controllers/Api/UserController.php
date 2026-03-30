<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['bikes', 'routes', 'maintenanceLogs'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'bikes_count' => $user->bikes_count,
                    'routes_count' => $user->routes_count,
                    'maintenance_logs_count' => $user->maintenance_logs_count,
                    'total_km' => $user->total_km,
                    'created_at' => $user->created_at,
                ];
            })
        ]);
    }
}
