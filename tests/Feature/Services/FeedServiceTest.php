<?php

namespace Tests\Feature\Services;

use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use App\Notifications\RouteSharedNotification;
use App\Services\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeedServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeedService;
    }

    public function test_create_text_post_does_not_dispatch_notification(): void
    {
        Notification::fake();
        $author = User::factory()->create();

        $post = $this->service->createPost($author, [
            'content' => 'Hallo wereld',
            'type' => 'text',
        ]);

        $this->assertSame('text', $post->type);
        $this->assertSame($author->id, $post->user_id);
        Notification::assertNothingSent();
    }

    public function test_create_route_share_dispatches_to_all_other_users(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $otherA = User::factory()->create();
        $otherB = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);

        $post = $this->service->createPost($author, [
            'content' => 'Mooie rit!',
            'type' => 'route_share',
            'route_id' => $route->id,
        ]);

        $this->assertSame('route_share', $post->type);
        $this->assertSame($route->id, $post->route_id);

        Notification::assertSentTo($otherA, RouteSharedNotification::class, function (RouteSharedNotification $n) use ($post, $route, $author): bool {
            return $n->post->is($post) && $n->route->is($route) && $n->actor->is($author);
        });
        Notification::assertSentTo($otherB, RouteSharedNotification::class);
        Notification::assertNotSentTo($author, RouteSharedNotification::class);
    }

    public function test_route_share_for_public_route_owned_by_someone_else_still_fanouts(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $bystander = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $owner->id, 'is_public' => true]);

        $this->service->createPost($author, [
            'content' => 'Check deze van een andere rider',
            'type' => 'route_share',
            'route_id' => $route->id,
        ]);

        Notification::assertSentTo($owner, RouteSharedNotification::class);
        Notification::assertSentTo($bystander, RouteSharedNotification::class);
        Notification::assertNotSentTo($author, RouteSharedNotification::class);
    }

    public function test_feed_returns_posts_in_descending_order_with_eager_loaded_relations(): void
    {
        $author = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);

        $older = Post::factory()->routeShare($route)->create([
            'user_id' => $author->id,
            'created_at' => now()->subHours(2),
        ]);
        $newer = Post::factory()->text()->create([
            'user_id' => $author->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $paginator = $this->service->feed(perPage: 25);

        $this->assertSame(2, $paginator->total());
        $this->assertSame($newer->id, $paginator->items()[0]->id);
        $this->assertSame($older->id, $paginator->items()[1]->id);
        $this->assertTrue($paginator->items()[0]->relationLoaded('user'));
        $this->assertTrue($paginator->items()[1]->relationLoaded('route'));
    }

    public function test_feed_respects_per_page_limit(): void
    {
        $author = User::factory()->create();
        Post::factory()->count(15)->text()->create(['user_id' => $author->id]);

        $paginator = $this->service->feed(perPage: 10);

        $this->assertSame(10, $paginator->perPage());
        $this->assertCount(10, $paginator->items());
        $this->assertSame(15, $paginator->total());
        $this->assertSame(2, $paginator->lastPage());
    }
}
