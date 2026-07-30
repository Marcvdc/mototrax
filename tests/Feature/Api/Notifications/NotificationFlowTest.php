<?php

namespace Tests\Feature\Api\Notifications;

use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use App\Notifications\RouteSharedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    public function test_unauthenticated_index_returns_401(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_index_returns_empty_collection_when_no_notifications(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_route_share_creates_persisted_notification_for_recipient(): void
    {
        $author = User::factory()->create();
        $recipient = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);
        $post = Post::factory()->routeShare($route)->create(['user_id' => $author->id]);

        $recipient->notify(new RouteSharedNotification($post, $route, $author));

        $response = $this->actingAs($recipient, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.post_id', $post->id)
            ->assertJsonPath('data.0.data.route_id', $route->id)
            ->assertJsonPath('data.0.data.actor_id', $author->id);
    }

    public function test_mark_read_updates_read_at(): void
    {
        $author = User::factory()->create();
        $recipient = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);
        $post = Post::factory()->routeShare($route)->create(['user_id' => $author->id]);
        $recipient->notify(new RouteSharedNotification($post, $route, $author));

        $notification = $recipient->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($recipient->notifications()->firstOrFail()->read_at);
    }

    public function test_mark_all_read_marks_every_notification_as_read(): void
    {
        $author = User::factory()->create();
        $recipient = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);
        $post = Post::factory()->routeShare($route)->create(['user_id' => $author->id]);
        $recipient->notify(new RouteSharedNotification($post, $route, $author));
        $recipient->notify(new RouteSharedNotification($post, $route, $author));

        $this->assertSame(2, $recipient->unreadNotifications()->count());

        $this->actingAs($recipient, 'sanctum')
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $recipient->refresh()->unreadNotifications()->count());
    }

    public function test_marking_someone_elses_notification_returns_404(): void
    {
        $author = User::factory()->create();
        $recipient = User::factory()->create();
        $intruder = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);
        $post = Post::factory()->routeShare($route)->create(['user_id' => $author->id]);
        $recipient->notify(new RouteSharedNotification($post, $route, $author));

        $notification = $recipient->notifications()->firstOrFail();

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertStatus(404);
    }
}
