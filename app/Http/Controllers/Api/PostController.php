<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private readonly FeedService $feedService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', FeedService::PER_PAGE_DEFAULT);

        if (! in_array($perPage, FeedService::PER_PAGE_OPTIONS, true)) {
            $perPage = FeedService::PER_PAGE_DEFAULT;
        }

        return PostResource::collection($this->feedService->feed($perPage));
    }

    public function show(Request $request, Post $post): PostResource
    {
        Gate::authorize('view', $post);

        return new PostResource($post->loadMissing(['user', 'route', 'maintenanceLog']));
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->feedService->createPost($request->user(), $request->validated());

        return (new PostResource($post->loadMissing(['user', 'route', 'maintenanceLog'])))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }
}
