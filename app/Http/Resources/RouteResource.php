<?php

namespace App\Http\Resources;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Route
 */
class RouteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'difficulty' => $this->difficulty,
            'tags' => $this->tags ?? [],
            'distance_km' => (float) $this->distance,
            'formatted_distance' => $this->formatted_distance,
            'estimated_time_minutes' => $this->estimated_time,
            'formatted_time' => $this->formatted_time,
            'waypoint_count' => $this->waypoint_count,
            'bbox' => $this->bbox,
            'start' => $this->when($this->start_lat !== null, fn (): array => [
                'lat' => (float) $this->start_lat,
                'lng' => (float) $this->start_lng,
            ]),
            'end' => $this->when($this->end_lat !== null, fn (): array => [
                'lat' => (float) $this->end_lat,
                'lng' => (float) $this->end_lng,
            ]),
            'gpx_url' => route('api.routes.gpx', ['route' => $this->id]),
            'preview_url' => route('api.routes.show', ['route' => $this->id]),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
