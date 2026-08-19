<?php

use Illuminate\Support\Facades\Route;

/*
 * Fallback delivery of the tracker script.
 *
 * The tracker is normally served straight off disk from public/vendor/kai-personalize/js, put
 * there by `php artisan vendor:publish --tag=kai-personalize-assets`. These routes only catch
 * installations where that never happened: serving an 8 KB static file through the full
 * framework boot works, but costs an order of magnitude more time and occupies a PHP worker
 * per visitor.
 *
 * The cache lifetime is deliberately short and not immutable. These URLs carry no version, so
 * anything longer would leave returning visitors on a stale tracker after an upgrade. The
 * published path has a ?v= query for that instead.
 */
Route::prefix('kai-personalize')
    ->name('kai-personalize.')
    ->middleware(['web'])
    ->group(function () {
        $serve = function (string $file) {
            $path = __DIR__.'/../resources/js/'.$file;

            if (! file_exists($path)) {
                abort(404);
            }

            return response()->file($path, [
                'Content-Type' => 'text/javascript; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        };

        Route::get('/tracker.js', fn () => $serve('tracker.js'))->name('tracker');
        Route::get('/tracker.min.js', fn () => $serve('tracker.min.js'))->name('tracker-min');
    });
