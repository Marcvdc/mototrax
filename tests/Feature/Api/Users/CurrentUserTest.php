<?php

namespace Tests\Feature\Api\Users;

use App\Enums\MotorType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_user_is_returned_as_user_resource(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'location' => 'Nijmegen',
            'motor_type' => MotorType::Klassiek,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.location', 'Nijmegen')
            ->assertJsonPath('data.motor_type.value', 'klassiek')
            ->assertJsonPath('data.motor_type.label', 'Klassiek')
            ->assertJsonPath('data.avatar_url', null)
            ->assertJsonMissingPath('data.is_admin')
            ->assertJsonMissingPath('data.avatar');
    }

    public function test_current_user_requires_authentication(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }
}
