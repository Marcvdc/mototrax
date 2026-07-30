<?php

namespace Tests\Feature\Api\Feed;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $post = Post::factory()->text()->create(['user_id' => User::factory()->create()->id]);

        $this->deleteJson("/api/v1/posts/{$post->id}")->assertStatus(401);
    }

    public function test_owner_can_delete_own_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->text()->create(['user_id' => $author->id]);

        $this->actingAs($author, 'sanctum')->deleteJson("/api/v1/posts/{$post->id}")
            ->assertOk();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_non_owner_cannot_delete_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->text()->create(['user_id' => $author->id]);

        $this->actingAs($other, 'sanctum')->deleteJson("/api/v1/posts/{$post->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
