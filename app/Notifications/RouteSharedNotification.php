<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RouteSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Post $post,
        public readonly Route $route,
        public readonly User $actor,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'route_id' => $this->route->id,
            'route_name' => $this->route->name,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
