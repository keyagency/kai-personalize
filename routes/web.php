<?php

use Illuminate\Support\Facades\Route;

Route::prefix('kai-personalize')
    ->name('kai-personalize.')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/tracker.js', function () {
            $path = __DIR__.'/../resources/js/tracker.js';

            if (!file_exists($path)) {
                abort(404);
            }

            return response()->file($path, [
                'Content-Type' => 'text/javascript; charset=utf-8',
                'Cache-Control' => 'public, max-age=86400, immutable',
            ]);
        })->name('tracker');

        Route::get('/tracker.min.js', function () {
            $path = __DIR__.'/../resources/js/tracker.min.js';

            if (!file_exists($path)) {
                abort(404);
            }

            return response()->file($path, [
                'Content-Type' => 'text/javascript; charset=utf-8',
                'Cache-Control' => 'public, max-age=86400, immutable',
            ]);
        })->name('tracker-min');
    });
