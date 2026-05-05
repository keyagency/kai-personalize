<?php

use Illuminate\Support\Facades\Route;
use KeyAgency\KaiPersonalize\Http\Controllers\ApiConnectionsController;
use KeyAgency\KaiPersonalize\Http\Controllers\BlacklistController;
use KeyAgency\KaiPersonalize\Http\Controllers\DashboardController;
use KeyAgency\KaiPersonalize\Http\Controllers\PageAnalyticsController;
use KeyAgency\KaiPersonalize\Http\Controllers\RulesController;
use KeyAgency\KaiPersonalize\Http\Controllers\SegmentsController;
use KeyAgency\KaiPersonalize\Http\Controllers\SettingsController;
use KeyAgency\KaiPersonalize\Http\Controllers\VisitorsController;

Route::prefix('kai-personalize')->name('kai-personalize.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/data', [DashboardController::class, 'data'])->name('data');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Rules Management
    Route::prefix('rules')->name('rules.')->group(function () {
        Route::get('/', [RulesController::class, 'index'])->name('index');
        Route::get('/create', [RulesController::class, 'create'])->name('create');
        Route::post('/', [RulesController::class, 'store'])->name('store');
        Route::get('/{id}', [RulesController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [RulesController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RulesController::class, 'update'])->name('update');
        Route::delete('/{id}', [RulesController::class, 'destroy'])->name('destroy');
    });

    // Visitors Management
    Route::prefix('visitors')->name('visitors.')->group(function () {
        Route::get('/', [VisitorsController::class, 'index'])->name('index');
        Route::get('/{id}', [VisitorsController::class, 'show'])->name('show');
        Route::delete('/{id}', [VisitorsController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/blacklist', [VisitorsController::class, 'blacklist'])->name('blacklist');
    });

    // Segments Management
    Route::prefix('segments')->name('segments.')->group(function () {
        Route::get('/', [SegmentsController::class, 'index'])->name('index');
        Route::get('/create', [SegmentsController::class, 'create'])->name('create');
        Route::post('/', [SegmentsController::class, 'store'])->name('store');
        Route::get('/{id}', [SegmentsController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SegmentsController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SegmentsController::class, 'update'])->name('update');
        Route::delete('/{id}', [SegmentsController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/refresh', [SegmentsController::class, 'refresh'])->name('refresh');
    });

    // API Connections Management
    Route::prefix('api-connections')->name('api-connections.')->group(function () {
        Route::get('/', [ApiConnectionsController::class, 'index'])->name('index');
        Route::get('/create', [ApiConnectionsController::class, 'create'])->name('create');
        Route::post('/', [ApiConnectionsController::class, 'store'])->name('store');
        Route::get('/{id}', [ApiConnectionsController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ApiConnectionsController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ApiConnectionsController::class, 'update'])->name('update');
        Route::delete('/{id}', [ApiConnectionsController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/test', [ApiConnectionsController::class, 'test'])->name('test');
        Route::delete('/{id}/cache', [ApiConnectionsController::class, 'clearCache'])->name('clear-cache');
    });

    // Blacklist Management
    Route::prefix('blacklists')->name('blacklists.')->group(function () {
        Route::get('/', [BlacklistController::class, 'index'])->name('index');
        Route::get('/create', [BlacklistController::class, 'create'])->name('create');
        Route::post('/', [BlacklistController::class, 'store'])->name('store');
        Route::get('/{blacklist}/edit', [BlacklistController::class, 'edit'])->name('edit');
        Route::put('/{blacklist}', [BlacklistController::class, 'update'])->name('update');
        Route::delete('/{blacklist}', [BlacklistController::class, 'destroy'])->name('destroy');
        Route::post('/{blacklist}/toggle', [BlacklistController::class, 'toggle'])->name('toggle');
        Route::get('/{blacklist}/logs', [BlacklistController::class, 'logs'])->name('logs');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('pages', [PageAnalyticsController::class, 'index'])->name('pages');
        Route::get('pages/data', [PageAnalyticsController::class, 'data'])->name('data');
        Route::get('pages/{slug}', [PageAnalyticsController::class, 'showBySlug'])->name('pages.show');
        Route::get('pages/detail', [PageAnalyticsController::class, 'show'])->name('pages.detail');
    });

    // Other routes disabled until controllers are implemented

    // // Segments
    // Route::prefix('segments')->name('segments.')->group(function () {
    //     Route::get('/', 'SegmentsController@index')->name('index');
    //     Route::get('/create', 'SegmentsController@create')->name('create');
    //     Route::post('/', 'SegmentsController@store')->name('store');
    //     Route::get('/{id}', 'SegmentsController@show')->name('show');
    //     Route::get('/{id}/edit', 'SegmentsController@edit')->name('edit');
    //     Route::put('/{id}', 'SegmentsController@update')->name('update');
    //     Route::delete('/{id}', 'SegmentsController@destroy')->name('destroy');
    // });

    // // API Connections
    // Route::prefix('api-connections')->name('api-connections.')->group(function () {
    //     Route::get('/', 'ApiConnectionsController@index')->name('index');
    //     Route::get('/create', 'ApiConnectionsController@create')->name('create');
    //     Route::post('/', 'ApiConnectionsController@store')->name('store');
    //     Route::get('/{id}', 'ApiConnectionsController@show')->name('show');
    //     Route::get('/{id}/edit', 'ApiConnectionsController@edit')->name('edit');
    //     Route::put('/{id}', 'ApiConnectionsController@update')->name('update');
    //     Route::delete('/{id}', 'ApiConnectionsController@destroy')->name('destroy');
    //     Route::post('/{id}/test', 'ApiConnectionsController@test')->name('test');
    //     Route::delete('/{id}/cache', 'ApiConnectionsController@clearCache')->name('clear-cache');
    // });

    // // Settings
    // Route::get('/settings', 'SettingsController@index')->name('settings');
    // Route::post('/settings', 'SettingsController@update')->name('settings.update');
});
