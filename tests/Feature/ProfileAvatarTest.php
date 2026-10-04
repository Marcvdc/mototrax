<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(ProfileService::DISK);
    }

    public function test_avatar_can_be_uploaded(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('avatar.png')]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertStringStartsWith(ProfileService::DIRECTORY.'/', $user->avatar);
        Storage::disk(ProfileService::DISK)->assertExists($user->avatar);
        $this->assertSame(Storage::disk(ProfileService::DISK)->url($user->avatar), $user->avatar_url);
    }

    public function test_uploading_a_new_avatar_removes_the_previous_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('first.png')]));
        $firstAvatar = $user->refresh()->avatar;

        $this->actingAs($user)->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('second.png')]));
        $secondAvatar = $user->refresh()->avatar;

        $this->assertNotSame($firstAvatar, $secondAvatar);
        Storage::disk(ProfileService::DISK)->assertMissing($firstAvatar);
        Storage::disk(ProfileService::DISK)->assertExists($secondAvatar);
    }

    public function test_avatar_can_be_removed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('avatar.png')]));
        $avatar = $user->refresh()->avatar;

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['remove_avatar' => '1']))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNull($user->avatar);
        $this->assertNull($user->avatar_url);
        Storage::disk(ProfileService::DISK)->assertMissing($avatar);
    }

    public function test_saving_without_a_new_file_keeps_the_current_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('avatar.png')]));
        $avatar = $user->refresh()->avatar;

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['location' => 'Zwolle']))
            ->assertSessionHasNoErrors();

        $this->assertSame($avatar, $user->refresh()->avatar);
        Storage::disk(ProfileService::DISK)->assertExists($avatar);
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->refresh()->avatar);
    }

    public function test_avatar_larger_than_two_megabytes_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('big.png')->size(2049)]))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->refresh()->avatar);
    }

    public function test_profile_pages_render_the_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->profilePayload($user, ['avatar' => UploadedFile::fake()->image('avatar.png')]));
        $user->refresh();

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee($user->avatar_url, escape: false);
        $this->actingAs($user)->get('/profile/edit')->assertOk()->assertSee($user->avatar_url, escape: false);
    }

    public function test_initials_are_shown_without_an_avatar(): void
    {
        $user = User::factory()->create(['name' => 'Sanne Bakker']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('SB');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(User $user, array $overrides = []): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            ...$overrides,
        ];
    }
}
