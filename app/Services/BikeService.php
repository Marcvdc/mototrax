<?php

namespace App\Services;

use App\Models\Bike;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class BikeService
{
    public const DISK = 'public';

    public const DIRECTORY = 'bikes';

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Bike
    {
        return Bike::create([
            ...$this->persistImage($attributes),
            'user_id' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Bike $bike, array $attributes): Bike
    {
        $bike->update($this->persistImage($attributes));

        return $bike;
    }

    /**
     * Slaat een geüploade foto op de public disk op en vervangt de waarde door het pad.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function persistImage(array $attributes): array
    {
        if (($attributes['image'] ?? null) instanceof UploadedFile) {
            $attributes['image'] = $attributes['image']->store(self::DIRECTORY, self::DISK);
        }

        return $attributes;
    }
}
