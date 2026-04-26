<?php

namespace App\Policies;

use App\Models\Route;
use App\Models\User;

class RoutePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Route $route): bool
    {
        if ($route->is_public) {
            return true;
        }

        return $user !== null && $user->id === $route->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Route $route): bool
    {
        return $user->id === $route->user_id;
    }

    public function delete(User $user, Route $route): bool
    {
        return $user->id === $route->user_id;
    }

    public function download(?User $user, Route $route): bool
    {
        return $this->view($user, $route);
    }
}
