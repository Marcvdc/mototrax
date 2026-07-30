<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBikeRequest;
use App\Http\Requests\Api\UpdateBikeRequest;
use App\Http\Resources\BikeResource;
use App\Models\Bike;
use App\Services\BikeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BikeController extends Controller
{
    public function __construct(private readonly BikeService $bikeService) {}

    public function index(): AnonymousResourceCollection
    {
        $bikes = Bike::query()
            ->with('user')
            ->withCount('maintenanceLogs')
            ->latest()
            ->paginate(25);

        return BikeResource::collection($bikes);
    }

    public function store(StoreBikeRequest $request): BikeResource
    {
        $bike = $this->bikeService->create($request->user(), $request->validated());

        return new BikeResource($bike->load('user'));
    }

    public function update(UpdateBikeRequest $request, Bike $bike): BikeResource
    {
        $bike = $this->bikeService->update($bike, $request->validated());

        return new BikeResource($bike->load('user'));
    }

    public function destroy(Bike $bike): Response
    {
        Gate::authorize('delete', $bike);

        $bike->delete();

        return response()->noContent();
    }
}
