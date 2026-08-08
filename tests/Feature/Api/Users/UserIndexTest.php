<?php

namespace Tests\Feature\Api\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_paginated(): void
    {
        User::factory()->count(3)->create();

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'bikes_count', 'routes_count', 'maintenance_logs_count', 'total_km', 'created_at']],
                'links',
                'meta',
            ]);
    }

    public function test_email_is_not_leaked_to_anonymous_visitors(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users')->assertOk();

        foreach ($response->json('data') as $user) {
            $this->assertArrayNotHasKey('email', $user);
        }
    }

    public function test_authenticated_user_sees_only_their_own_email(): void
    {
        $me = User::factory()->create();
        User::factory()->count(2)->create();

        $response = $this->actingAs($me)->getJson('/api/v1/users')->assertOk();

        foreach ($response->json('data') as $user) {
            if ($user['id'] === $me->id) {
                $this->assertSame($me->email, $user['email']);
            } else {
                $this->assertArrayNotHasKey('email', $user);
            }
        }
    }
}
