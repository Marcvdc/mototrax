<?php

namespace Tests\Feature\Filament;

use App\Enums\MotorType;
use App\Filament\Auth\EditProfile;
use App\Models\User;
use App\Services\ProfileService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(ProfileService::DISK);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_profile_page_renders_in_the_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Motor-type');
    }

    public function test_profile_fields_can_be_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'location' => 'Enschede',
                'motor_type' => MotorType::Scooter->value,
                'avatar' => UploadedFile::fake()->image('avatar.png'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Enschede', $user->location);
        $this->assertSame(MotorType::Scooter, $user->motor_type);
        $this->assertNotNull($user->avatar);
        Storage::disk(ProfileService::DISK)->assertExists($user->avatar);
    }

    public function test_replacing_the_avatar_removes_the_previous_file(): void
    {
        $disk = Storage::disk(ProfileService::DISK);
        $disk->put('avatars/old.jpg', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['avatar' => []])
            ->fillForm(['avatar' => UploadedFile::fake()->image('new.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotSame('avatars/old.jpg', $user->refresh()->avatar);
        $disk->assertExists($user->avatar);
        $disk->assertMissing('avatars/old.jpg');
    }

    public function test_removing_the_avatar_deletes_the_file(): void
    {
        $disk = Storage::disk(ProfileService::DISK);
        $disk->put('avatars/old.jpg', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['avatar' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($user->refresh()->avatar);
        $disk->assertMissing('avatars/old.jpg');
    }

    public function test_a_tampered_avatar_path_is_rejected_and_the_target_file_is_kept(): void
    {
        $disk = Storage::disk(ProfileService::DISK);
        $disk->put('avatars/someone-else.jpg', 'victim');
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data.avatar', ['tampered' => 'avatars/someone-else.jpg'])
            ->call('save')
            ->assertHasFormErrors(['avatar']);

        $this->assertNull($user->refresh()->avatar);
        $disk->assertExists('avatars/someone-else.jpg');
    }
}
