<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProfileService
{
    public const DISK = 'public';

    public const DIRECTORY = 'avatars';

    /**
     * Werkt het profiel bij. Een `avatar`-sleutel vervangt de huidige avatar: een upload wordt
     * opgeslagen, een pad (Filament heeft al opgeslagen) wordt overgenomen en `null` haalt hem weg.
     * Zonder `avatar`-sleutel blijft de huidige avatar staan. Mislukt het opslaan van de gebruiker,
     * dan wordt een net opgeslagen upload weer verwijderd, zodat er geen weesbestand achterblijft.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $previousAvatar = $user->avatar;
        $storedUpload = null;

        if (($attributes['avatar'] ?? null) instanceof UploadedFile) {
            $storedUpload = $attributes['avatar']->store(self::DIRECTORY, self::DISK);
            $attributes['avatar'] = $storedUpload;
        }

        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        try {
            $user->save();
        } catch (Throwable $exception) {
            if (is_string($storedUpload)) {
                Storage::disk(self::DISK)->delete($storedUpload);
            }

            throw $exception;
        }

        if ($previousAvatar !== null && $previousAvatar !== $user->avatar) {
            Storage::disk(self::DISK)->delete($previousAvatar);
        }

        return $user;
    }

    /**
     * Verwijdert het account en daarna het avatarbestand, zodat de foto niet publiek bereikbaar
     * blijft via de oude URL. Het bestand gaat alleen weg als het record echt verwijderd is.
     */
    public function delete(User $user): void
    {
        $avatar = $user->avatar;

        if ($user->delete() && $avatar !== null) {
            Storage::disk(self::DISK)->delete($avatar);
        }
    }
}
