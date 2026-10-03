<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\OutbreakController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('scans', ScanController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/outbreaks/{outbreak}/close', [OutbreakController::class, 'close'])->name('outbreaks.close');
    Route::resource('reports', ReportController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/map', [MapController::class, 'index'])->name('map.index');
});

require __DIR__.'/auth.php';
