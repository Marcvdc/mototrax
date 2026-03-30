<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BikeController;
use App\Http\Controllers\Api\RouteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::get('/users', [UserController::class, 'index']);
Route::get('/bikes', [BikeController::class, 'index']);
Route::get('/routes', [RouteController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bikes', [BikeController::class, 'store']);
    Route::put('/bikes/{bike}', [BikeController::class, 'update']);
    Route::delete('/bikes/{bike}', [BikeController::class, 'delete']);
    
    Route::post('/routes', [RouteController::class, 'store']);
    Route::put('/routes/{route}', [RouteController::class, 'update']);
    Route::delete('/routes/{route}', [RouteController::class, 'delete']);
});
