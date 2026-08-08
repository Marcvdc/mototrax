<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // E-mail wordt uitsluitend aan de gebruiker zelf getoond — nooit aan anderen.
            'email' => $this->when(
                $request->user()?->id === $this->id,
                fn (): string => $this->email,
            ),
            'bikes_count' => $this->bikes_count,
            'routes_count' => $this->routes_count,
            'maintenance_logs_count' => $this->maintenance_logs_count,
            'total_km' => $this->total_km,
            'created_at' => $this->created_at,
        ];
    }
}
