<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::resource('scans', ScanController::class)->only('create', 'store', 'show');
