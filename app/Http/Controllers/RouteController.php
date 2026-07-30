<?php

namespace App\Http\Controllers;

use App\Models\Route as RouteModel;
use App\Services\Gpx\InvalidGpxException;
use App\Services\RouteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function __construct(private readonly RouteService $routeService) {}

    public function show(Request $request, RouteModel $route): View
    {
        abort_unless($route->is_public, 404);

        try {
            $geojson = $this->routeService->toGeoJson($route);
        } catch (InvalidGpxException) {
            $geojson = null;
        }

        return view('routes.show', [
            'route' => $route->loadMissing('user'),
            'geojson' => $geojson,
        ]);
    }
}
