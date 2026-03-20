<?php

use Illuminate\Support\Facades\Route;

Route::prefix('kai-personalize')
    ->name('kai-personalize.')
    ->middleware(['web'])
    ->get('/tracker.js', function () {
        $path = __DIR__.'/../resources/js/tracker.js';
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path, [
            'Content-Type' => 'text/javascript',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    })->name('tracker');
