<?php

use App\Http\Controllers\YellowFormController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('dashboard');
})->name('home');

    // Yellow Form routes
Route::post('/yellow-forms', [YellowFormController::class, 'store'])->name('yellow-forms.store');

// API routes
Route::get('/api/violations', [YellowFormController::class, 'getViolations']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
