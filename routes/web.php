<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('weather', [App\Http\Controllers\WeatherController::class, 'show'])->name('weather.show');
    Route::get('api/weather', [App\Http\Controllers\WeatherController::class, 'api'])->name('weather.api');
    Route::get('map', [App\Http\Controllers\MarkerController::class, 'index'])->name('map.index');

    Route::prefix('api/markers')->group(function () {
        Route::get('/', [App\Http\Controllers\MarkerController::class, 'apiIndex'])->name('markers.api.index');
        Route::post('/', [App\Http\Controllers\MarkerController::class, 'store'])->name('markers.store');
        Route::get('/{marker}', [App\Http\Controllers\MarkerController::class, 'show'])->name('markers.show');
        Route::put('/{marker}', [App\Http\Controllers\MarkerController::class, 'update'])->name('markers.update');
        Route::delete('/{marker}', [App\Http\Controllers\MarkerController::class, 'destroy'])->name('markers.destroy');
    });

    Route::get('blog', [PostController::class, 'index'])->name('blog.index');
    Route::post('blog', [PostController::class, 'store'])->name('blog.store');
    Route::put('blog/{post}', [PostController::class, 'update'])->name('blog.update');
    Route::delete('blog/{post}', [PostController::class, 'destroy'])->name('blog.destroy');

    Route::post('blog/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

require __DIR__.'/settings.php';
