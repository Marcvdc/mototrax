<?php

namespace App\Policies;

use App\Models\Bike;
use App\Models\User;

class BikePolicy
{
    public function update(User $user, Bike $bike): bool
    {
        return $user->id === $bike->user_id;
    }

    public function delete(User $user, Bike $bike): bool
    {
        return $user->id === $bike->user_id;
    }
}
