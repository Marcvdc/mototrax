<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $users = User::query()
            ->latest()
            ->paginate(25);

        return UserResource::collection($users);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
