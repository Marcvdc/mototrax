<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use Illuminate\Http\Request;

class BikeController extends Controller
{
    public function index()
    {
        $bikes = Bike::with(['user', 'maintenanceLogs'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $bikes->map(function ($bike) {
                return [
                    'id' => $bike->id,
                    'brand' => $bike->brand,
                    'model' => $bike->model,
                    'year' => $bike->year,
                    'km_current' => $bike->km_current,
                    'image_url' => $bike->image_url,
                    'description' => $bike->description,
                    'user' => [
                        'id' => $bike->user->id,
                        'name' => $bike->user->name,
                    ],
                    'maintenance_logs_count' => $bike->maintenanceLogs->count(),
                    'created_at' => $bike->created_at,
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'km_current' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $validated['user_id'] = auth()->id();
        
        $bike = Bike::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Bike created successfully',
            'data' => $bike
        ], 201);
    }

    public function update(Request $request, Bike $bike)
    {
        if ($bike->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'brand' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|nullable|integer|min:1900|max:' . date('Y'),
            'km_current' => 'sometimes|nullable|integer|min:0',
            'description' => 'sometimes|nullable|string',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $bike->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Bike updated successfully',
            'data' => $bike
        ]);
    }

    public function destroy(Bike $bike)
    {
        if ($bike->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $bike->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Bike deleted successfully'
        ]);
    }
}
