<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use App\Notifications\RouteSharedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FeedService
{
    public const PER_PAGE_DEFAULT = 25;

    public const PER_PAGE_OPTIONS = [10, 25, 50];

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function feed(int $perPage = self::PER_PAGE_DEFAULT): LengthAwarePaginator
    {
        return Post::query()->forFeed()->paginate($perPage);
    }

    /**
     * @param  array{content: string, type: string, route_id?: int|null, maintenance_log_id?: int|null}  $data
     */
    public function createPost(User $author, array $data): Post
    {
        return DB::transaction(function () use ($author, $data): Post {
            $post = $author->posts()->create([
                'content' => $data['content'],
                'type' => $data['type'],
                'route_id' => $data['route_id'] ?? null,
                'maintenance_log_id' => $data['maintenance_log_id'] ?? null,
            ]);

            if ($post->type === 'route_share' && $post->route_id !== null) {
                $this->dispatchRouteShared($post, $author);
            }

            return $post;
        });
    }

    private function dispatchRouteShared(Post $post, User $actor): void
    {
        $route = Route::query()->find($post->route_id);

        if ($route === null) {
            return;
        }

        $recipients = User::query()->where('id', '!=', $actor->id)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new RouteSharedNotification($post, $route, $actor));
    }
}
