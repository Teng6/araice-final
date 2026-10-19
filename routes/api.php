<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:scans');
    Route::get('/scans', [ScanController::class, 'index']);
    Route::get('/scans/{scan}', [ScanController::class, 'show']);
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markRead']);
    Route::get('/map', [MapController::class, 'index']);
});
