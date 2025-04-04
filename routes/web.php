<?php

use App\Http\Controllers\YellowFormController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes (accessible by both authenticated and non-authenticated users)
Route::get('/', function () {
    return Inertia::render('YellowFormSearch');
})->name('home')->withoutMiddleware([\App\Http\Middleware\RedirectBasedOnRole::class]);

Route::get('/api/yellow-forms/search', [YellowFormController::class, 'search'])
    ->name('api.yellow-forms.search')
    ->withoutMiddleware([\App\Http\Middleware\RedirectBasedOnRole::class]);

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::post('/yellow-forms', [YellowFormController::class, 'store'])->name('yellow-forms.store');
    Route::get('/api/violations', [YellowFormController::class, 'getViolations']);
    Route::get('/api/departments-courses', [YellowFormController::class, 'getDepartmentsAndCourses'])->name('api.departments-courses');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
