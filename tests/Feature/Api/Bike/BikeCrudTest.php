<?php

namespace Tests\Feature\Api\Bike;

use App\Models\Bike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BikeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_public_and_paginated(): void
    {
        Bike::factory()->count(2)->create();

        $this->getJson('/api/v1/bikes')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'brand', 'model', 'year', 'km_current', 'image_url', 'user', 'maintenance_logs_count', 'created_at']],
                'links',
                'meta',
            ]);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/bikes', ['brand' => 'Honda', 'model' => 'CB500X'])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_bike(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bikes', ['brand' => 'Honda', 'model' => 'CB500X', 'year' => 2022, 'km_current' => 1000])
            ->assertCreated()
            ->assertJsonPath('data.brand', 'Honda')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('bikes', ['brand' => 'Honda', 'model' => 'CB500X', 'user_id' => $user->id]);
    }

    public function test_store_validation_requires_brand(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/bikes', ['model' => 'CB500X'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('brand');
    }

    public function test_owner_can_update_bike(): void
    {
        $user = User::factory()->create();
        $bike = Bike::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/bikes/{$bike->id}", ['km_current' => 5000])
            ->assertOk()
            ->assertJsonPath('data.km_current', 5000);
    }

    public function test_non_owner_cannot_update_bike(): void
    {
        $bike = Bike::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/v1/bikes/{$bike->id}", ['km_current' => 5000])
            ->assertForbidden();
    }

    public function test_owner_can_delete_bike(): void
    {
        $user = User::factory()->create();
        $bike = Bike::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/bikes/{$bike->id}")->assertNoContent();

        $this->assertDatabaseMissing('bikes', ['id' => $bike->id]);
    }

    public function test_non_owner_cannot_delete_bike(): void
    {
        $bike = Bike::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/bikes/{$bike->id}")->assertForbidden();

        $this->assertDatabaseHas('bikes', ['id' => $bike->id]);
    }
}
