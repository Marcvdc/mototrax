<?php

namespace Tests\Feature\Services;

use App\Enums\MotorType;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(ProfileService::DISK);
        $this->profileService = new ProfileService;
    }

    public function test_it_updates_location_and_motor_type(): void
    {
        $user = User::factory()->create();

        $this->profileService->update($user, ['location' => 'Deventer', 'motor_type' => 'cruiser']);

        $user->refresh();
        $this->assertSame('Deventer', $user->location);
        $this->assertSame(MotorType::Cruiser, $user->motor_type);
    }

    public function test_it_stores_an_uploaded_avatar_on_the_public_disk(): void
    {
        $user = User::factory()->create();

        $this->profileService->update($user, ['avatar' => UploadedFile::fake()->image('avatar.png')]);

        Storage::disk(ProfileService::DISK)->assertExists($user->refresh()->avatar);
    }

    public function test_an_already_stored_path_replaces_and_deletes_the_previous_avatar(): void
    {
        $disk = Storage::disk(ProfileService::DISK);
        $disk->put('avatars/old.jpg', 'old');
        $disk->put('avatars/new.jpg', 'new');
        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);

        $this->profileService->update($user, ['avatar' => 'avatars/new.jpg']);

        $this->assertSame('avatars/new.jpg', $user->refresh()->avatar);
        $disk->assertMissing('avatars/old.jpg');
        $disk->assertExists('avatars/new.jpg');
    }

    public function test_null_avatar_removes_the_file(): void
    {
        $disk = Storage::disk(ProfileService::DISK);
        $disk->put('avatars/old.jpg', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);

        $this->profileService->update($user, ['avatar' => null]);

        $this->assertNull($user->refresh()->avatar);
        $disk->assertMissing('avatars/old.jpg');
    }

    public function test_it_keeps_the_avatar_when_no_avatar_key_is_given(): void
    {
        $disk = Storage::disk(ProfileService::DISK);
        $disk->put('avatars/current.jpg', 'current');
        $user = User::factory()->create(['avatar' => 'avatars/current.jpg']);

        $this->profileService->update($user, ['name' => 'Nieuwe Naam']);

        $this->assertSame('avatars/current.jpg', $user->refresh()->avatar);
        $disk->assertExists('avatars/current.jpg');
    }

    public function test_changing_the_email_resets_verification(): void
    {
        $user = User::factory()->create();

        $this->profileService->update($user, ['email' => 'nieuw@example.com']);

        $this->assertNull($user->refresh()->email_verified_at);
    }
}
