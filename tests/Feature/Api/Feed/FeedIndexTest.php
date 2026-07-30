<?php

namespace Tests\Feature\Api\Feed;

use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/feed')->assertStatus(401);
    }

    public function test_returns_paginated_list_descending_by_created_at(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        $older = Post::factory()->text()->create([
            'user_id' => $author->id,
            'created_at' => now()->subHours(2),
        ]);
        $newer = Post::factory()->text()->create([
            'user_id' => $author->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_per_page_validates_to_allowed_values(): void
    {
        $viewer = User::factory()->create();
        Post::factory()->count(15)->text()->create(['user_id' => $viewer->id]);

        $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(10, 'data');

        $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed?per_page=999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 25);
    }

    public function test_route_payload_is_null_for_private_route_owned_by_someone_else(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $privateRoute = Route::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        $post = Post::factory()->routeShare($privateRoute)->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.route', null);
    }

    public function test_route_payload_present_for_public_route(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $publicRoute = Route::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        Post::factory()->routeShare($publicRoute)->create(['user_id' => $owner->id]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed');

        $response->assertOk()
            ->assertJsonPath('data.0.route.id', $publicRoute->id)
            ->assertJsonPath('data.0.route.is_public', true);
    }

    public function test_route_payload_visible_to_owner_even_when_route_is_private(): void
    {
        $owner = User::factory()->create();
        $privateRoute = Route::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        Post::factory()->routeShare($privateRoute)->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/feed');

        $response->assertOk()
            ->assertJsonPath('data.0.route.id', $privateRoute->id)
            ->assertJsonPath('data.0.route.is_public', false);
    }
}
