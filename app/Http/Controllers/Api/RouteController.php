<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRouteRequest;
use App\Http\Requests\Api\UpdateRouteRequest;
use App\Http\Resources\RouteResource as RouteApiResource;
use App\Models\Route;
use App\Services\Gpx\InvalidGpxException;
use App\Services\RouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RouteController extends Controller
{
    public function __construct(private readonly RouteService $routeService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Route::query()->with('user')->latest();

        if ($request->user() === null) {
            $query->public();
        } else {
            $query->where(function ($q) use ($request): void {
                $q->where('is_public', true)
                    ->orWhere('user_id', $request->user()->id);
            });
        }

        return RouteApiResource::collection($query->get());
    }

    public function show(Request $request, Route $route): JsonResponse
    {
        Gate::authorize('view', $route);

        try {
            $geojson = $this->routeService->toGeoJson($route);
        } catch (InvalidGpxException $e) {
            return response()->json([
                'message' => 'GPX file is unavailable or unreadable.',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => (new RouteApiResource($route->loadMissing('user')))->toArray($request),
            'track' => $geojson,
        ]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        try {
            $route = $this->routeService->createFromUpload(
                $request->user(),
                $request->file('gpx_file'),
                $request->validated(),
            );
        } catch (InvalidGpxException $e) {
            return response()->json([
                'message' => 'Invalid GPX file.',
                'error' => $e->getMessage(),
            ], 422);
        }

        return (new RouteApiResource($route->loadMissing('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRouteRequest $request, Route $route): RouteApiResource
    {
        $route->update($request->validated());

        return new RouteApiResource($route->loadMissing('user'));
    }

    public function destroy(Request $request, Route $route): JsonResponse
    {
        Gate::authorize('delete', $route);

        if ($route->gpx_file !== null && Storage::disk(RouteService::DISK)->exists($route->gpx_file)) {
            Storage::disk(RouteService::DISK)->delete($route->gpx_file);
        }

        $route->delete();

        return response()->json(['message' => 'Route deleted.']);
    }

    public function download(Request $request, Route $route): StreamedResponse|JsonResponse
    {
        Gate::authorize('download', $route);

        if ($route->gpx_file === null || ! Storage::disk(RouteService::DISK)->exists($route->gpx_file)) {
            return response()->json(['message' => 'GPX file missing.'], 404);
        }

        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $route->name).'.gpx';

        return Storage::disk(RouteService::DISK)->download(
            $route->gpx_file,
            $filename,
            ['Content-Type' => 'application/gpx+xml'],
        );
    }
}
