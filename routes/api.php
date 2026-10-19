<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\FarmerController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\OutbreakController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::get('/municipalities', [MunicipalityController::class, 'index']);
    Route::get('/diseases', [DiseaseController::class, 'index']);
    Route::get('/diseases/{disease}', [DiseaseController::class, 'show']);
    Route::get('/farmers', [FarmerController::class, 'index']);
    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:scans');
    Route::get('/scans', [ScanController::class, 'index']);
    Route::get('/scans/{scan}', [ScanController::class, 'show']);
    Route::get('/outbreaks', [OutbreakController::class, 'index']);
    Route::get('/outbreaks/{outbreak}', [OutbreakController::class, 'show']);
    Route::post('/outbreaks/{outbreak}/close', [OutbreakController::class, 'close']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markRead']);
    Route::get('/map', [MapController::class, 'index']);
});
