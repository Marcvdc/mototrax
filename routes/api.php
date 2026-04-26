<?php

use App\Http\Controllers\Api\BikeController;
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
});
