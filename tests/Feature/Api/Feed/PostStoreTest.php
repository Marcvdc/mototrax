<?php

namespace Tests\Feature\Api\Feed;

use App\Models\Bike;
use App\Models\MaintenanceLog;
use App\Models\Route;
use App\Models\User;
use App\Notifications\RouteSharedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PostStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_post_creation_returns_401(): void
    {
        $this->postJson('/api/v1/posts', [])->assertStatus(401);
    }

    public function test_text_post_happy_path(): void
    {
        Notification::fake();
        $author = User::factory()->create();

        $response = $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Net afgereden',
            'type' => 'text',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.content', 'Net afgereden');

        $this->assertDatabaseCount('posts', 1);
        Notification::assertNothingSent();
    }

    public function test_route_share_to_public_route_dispatches_notification_to_others(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $other = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => true]);

        $response = $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Mooie rit!',
            'type' => 'route_share',
            'route_id' => $route->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'route_share')
            ->assertJsonPath('data.route.id', $route->id);

        Notification::assertSentTo($other, RouteSharedNotification::class);
        Notification::assertNotSentTo($author, RouteSharedNotification::class);
    }

    public function test_route_share_to_someone_elses_public_route_is_allowed(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $owner->id, 'is_public' => true]);

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Tip van een andere rider',
            'type' => 'route_share',
            'route_id' => $route->id,
        ])->assertCreated();

        Notification::assertSentTo($owner, RouteSharedNotification::class);
    }

    public function test_route_share_to_private_route_returns_422(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $route = Route::factory()->create(['user_id' => $author->id, 'is_public' => false]);

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Probeer te sharen',
            'type' => 'route_share',
            'route_id' => $route->id,
        ])->assertStatus(422)->assertJsonValidationErrors('route_id');

        Notification::assertNothingSent();
    }

    public function test_route_share_without_route_id_returns_422(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Geen route',
            'type' => 'route_share',
        ])->assertStatus(422)->assertJsonValidationErrors('route_id');
    }

    public function test_content_over_2000_chars_returns_422(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => str_repeat('a', 2001),
            'type' => 'text',
        ])->assertStatus(422)->assertJsonValidationErrors('content');
    }

    public function test_invalid_type_returns_422(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Hallo',
            'type' => 'shouting',
        ])->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_maintenance_share_for_own_log_is_allowed(): void
    {
        $author = User::factory()->create();
        $bike = Bike::factory()->create(['user_id' => $author->id]);
        $log = MaintenanceLog::factory()->create([
            'user_id' => $author->id,
            'bike_id' => $bike->id,
        ]);

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Ketting gesmeerd',
            'type' => 'maintenance',
            'maintenance_log_id' => $log->id,
        ])->assertCreated()
            ->assertJsonPath('data.type', 'maintenance');
    }

    public function test_maintenance_share_for_other_users_log_returns_422(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $bike = Bike::factory()->create(['user_id' => $other->id]);
        $log = MaintenanceLog::factory()->create([
            'user_id' => $other->id,
            'bike_id' => $bike->id,
        ]);

        $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Niet mijn log',
            'type' => 'maintenance',
            'maintenance_log_id' => $log->id,
        ])->assertStatus(422)->assertJsonValidationErrors('maintenance_log_id');
    }
}
