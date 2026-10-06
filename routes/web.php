<?php

use App\Http\Controllers\Admin\DiseaseController as AdminDiseaseController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\EncyclopediaController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\OutbreakController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanReviewController;
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

Route::get('/encyclopedia', [EncyclopediaController::class, 'index'])->name('encyclopedia.index');
Route::get('/encyclopedia/{disease}', [EncyclopediaController::class, 'show'])->name('encyclopedia.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(array_values(array_filter([
    'auth',
    config('auth.require_email_verification') ? 'verified' : null,
])))->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('diseases', AdminDiseaseController::class)
            ->except(['index', 'show']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/scans/unlinked', [ScanReviewController::class, 'index'])->name('scans.unlinked');
    Route::resource('scans', ScanController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/scans/{scan}/link-outbreak', [ScanReviewController::class, 'store'])->name('scans.link-outbreak');
    Route::patch('/outbreaks/{outbreak}/close', [OutbreakController::class, 'close'])->name('outbreaks.close');
    Route::resource('reports', ReportController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    Route::get('/outbreaks', [OutbreakController::class, 'index'])->name('outbreaks.index');
    Route::resource('alerts', AlertController::class)->only(['index', 'show']);
});

require __DIR__.'/auth.php';
