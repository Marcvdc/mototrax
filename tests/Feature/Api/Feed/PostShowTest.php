<?php

namespace Tests\Feature\Api\Feed;

use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $post = Post::factory()->text()->create(['user_id' => User::factory()->create()->id]);

        $this->getJson("/api/v1/posts/{$post->id}")->assertStatus(401);
    }

    public function test_returns_own_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->text()->create(['user_id' => $author->id]);

        $this->actingAs($author, 'sanctum')->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_returns_other_users_post(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->text()->create(['user_id' => $author->id]);

        $this->actingAs($viewer, 'sanctum')->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_private_route_payload_is_null_for_non_owner(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $privateRoute = Route::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        $post = Post::factory()->routeShare($privateRoute)->create(['user_id' => $owner->id]);

        $this->actingAs($viewer, 'sanctum')->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.route', null);
    }

    public function test_returns_404_for_unknown_post(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/posts/9999')
            ->assertStatus(404);
    }
}
