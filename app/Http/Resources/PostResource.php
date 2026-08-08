<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'route' => $this->routePayload($request),
            'maintenance_log' => $this->when(
                $this->type === 'maintenance' && $this->maintenanceLog !== null,
                fn (): array => [
                    'id' => $this->maintenanceLog->id,
                    'title' => $this->maintenanceLog->title,
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Geeft route-payload alleen als post.type=route_share, route bestaat
     * en (route is publiek of viewer is eigenaar). Anders null.
     */
    private function routePayload(Request $request): mixed
    {
        if ($this->type !== 'route_share' || $this->route === null) {
            return null;
        }

        $viewer = $request->user();
        $route = $this->route;
        assert($route instanceof Route);

        $isVisible = $route->is_public || ($viewer !== null && $viewer->id === $route->user_id);

        if (! $isVisible) {
            return null;
        }

        return [
            'id' => $route->id,
            'name' => $route->name,
            'is_public' => $route->is_public,
            'distance_km' => (float) $route->distance,
            'preview_url' => route('api.v1.routes.show', ['route' => $route->id]),
        ];
    }
}
