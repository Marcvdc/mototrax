<?php

use App\Http\Controllers\Api\BikeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    // Public read endpoints
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/bikes', [BikeController::class, 'index'])->name('bikes.index');

    Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
    Route::get('/routes/{route}', [RouteController::class, 'show'])->name('routes.show');
    Route::get('/routes/{route}/gpx', [RouteController::class, 'download'])->name('routes.gpx');

    // Authenticated read endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        })->name('user');

        Route::get('/feed', [PostController::class, 'index'])->name('feed.index');
        Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    });

    // Authenticated write endpoints — stricter rate limit
    Route::middleware(['auth:sanctum', 'throttle:api-write'])->group(function () {
        Route::post('/bikes', [BikeController::class, 'store'])->name('bikes.store');
        Route::put('/bikes/{bike}', [BikeController::class, 'update'])->name('bikes.update');
        Route::delete('/bikes/{bike}', [BikeController::class, 'destroy'])->name('bikes.destroy');

        Route::post('/routes', [RouteController::class, 'store'])->name('routes.store');
        Route::put('/routes/{route}', [RouteController::class, 'update'])->name('routes.update');
        Route::delete('/routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');

        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    });
});
