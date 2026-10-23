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
        $isSelf = $request->user()?->id === $this->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            // E-mail en locatie zijn persoonsgegevens: uitsluitend voor de gebruiker zelf.
            'email' => $this->when($isSelf, fn (): string => $this->email),
            'location' => $this->when($isSelf, fn (): ?string => $this->location),
            'motor_type' => $this->motor_type === null ? null : [
                'value' => $this->motor_type->value,
                'label' => $this->motor_type->getLabel(),
            ],
            'avatar_url' => $this->avatar_url,
            'bikes_count' => $this->bikes_count,
            'routes_count' => $this->routes_count,
            'maintenance_logs_count' => $this->maintenance_logs_count,
            'total_km' => $this->total_km,
            'created_at' => $this->created_at,
        ];
    }
}
