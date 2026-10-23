<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public const DISK = 'public';

    public const DIRECTORY = 'avatars';

    /**
     * Werkt het profiel bij. Een `avatar`-sleutel vervangt de huidige avatar: een upload wordt
     * opgeslagen, een pad (Filament heeft al opgeslagen) wordt overgenomen en `null` haalt hem weg.
     * Zonder `avatar`-sleutel blijft de huidige avatar staan.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $previousAvatar = $user->avatar;

        if (($attributes['avatar'] ?? null) instanceof UploadedFile) {
            $attributes['avatar'] = $attributes['avatar']->store(self::DIRECTORY, self::DISK);
        }

        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($previousAvatar !== null && $previousAvatar !== $user->avatar) {
            Storage::disk(self::DISK)->delete($previousAvatar);
        }

        return $user;
    }
}
