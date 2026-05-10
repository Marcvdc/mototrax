<?php

use App\Http\Controllers\Api\BikeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public read endpoints
Route::get('/users', [UserController::class, 'index']);
Route::get('/bikes', [BikeController::class, 'index']);

Route::get('/routes', [RouteController::class, 'index'])->name('api.routes.index');
Route::get('/routes/{route}', [RouteController::class, 'show'])->name('api.routes.show');
Route::get('/routes/{route}/gpx', [RouteController::class, 'download'])->name('api.routes.gpx');

// Protected (Sanctum) endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bikes', [BikeController::class, 'store']);
    Route::put('/bikes/{bike}', [BikeController::class, 'update']);
    Route::delete('/bikes/{bike}', [BikeController::class, 'destroy']);

    Route::post('/routes', [RouteController::class, 'store'])->name('api.routes.store');
    Route::put('/routes/{route}', [RouteController::class, 'update'])->name('api.routes.update');
    Route::delete('/routes/{route}', [RouteController::class, 'destroy'])->name('api.routes.destroy');

    Route::get('/feed', [PostController::class, 'index'])->name('api.feed.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.posts.show');
    Route::post('/posts', [PostController::class, 'store'])->name('api.posts.store');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('api.posts.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('api.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('api.notifications.read-all');
});
