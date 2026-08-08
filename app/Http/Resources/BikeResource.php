<?php

namespace App\Http\Resources;

use App\Models\Bike;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bike
 */
class BikeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'km_current' => $this->km_current,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'maintenance_logs_count' => $this->maintenance_logs_count ?? $this->maintenanceLogs()->count(),
            'created_at' => $this->created_at,
        ];
    }
}
