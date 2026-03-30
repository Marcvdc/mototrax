<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::with(['user'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $routes->map(function ($route) {
                return [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'distance' => $route->distance,
                    'formatted_distance' => $route->formatted_distance,
                    'estimated_time' => $route->estimated_time,
                    'formatted_time' => $route->formatted_time,
                    'difficulty' => $route->difficulty,
                    'tags' => $route->tags,
                    'gpx_url' => $route->gpx_url,
                    'user' => [
                        'id' => $route->user->id,
                        'name' => $route->user->name,
                    ],
                    'created_at' => $route->created_at,
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'gpx_file' => 'required|file|mimes:gpx,xml|max:10240',
            'distance' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|integer|min:1',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        if ($request->hasFile('gpx_file')) {
            $path = $request->file('gpx_file')->store('gpx', 'public');
            $validated['gpx_file'] = $path;
        }

        $validated['user_id'] = auth()->id();
        
        $route = Route::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Route created successfully',
            'data' => $route
        ], 201);
    }

    public function update(Request $request, Route $route)
    {
        if ($route->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'distance' => 'sometimes|nullable|numeric|min:0',
            'estimated_time' => 'sometimes|nullable|integer|min:1',
            'difficulty' => 'sometimes|nullable|in:easy,medium,hard',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'string',
        ]);

        $route->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Route updated successfully',
            'data' => $route
        ]);
    }

    public function destroy(Route $route)
    {
        if ($route->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $route->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Route deleted successfully'
        ]);
    }
}
