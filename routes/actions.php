<?php

use Illuminate\Support\Facades\Route;
use KeyAgency\KaiPersonalize\Http\Controllers\Api\TrackingController;
use KeyAgency\KaiPersonalize\Http\Middleware\ThrottleTracking;

// Note: prefix 'kai-personalize' is added automatically by
// Statamic's AddonServiceProvider::registerActionRoutes()
// TrackVisitor middleware is applied globally via ServiceProvider

Route::middleware([ThrottleTracking::class])->group(function () {
    // Visitor tracking endpoints
    Route::post('track', [TrackingController::class, 'track'])->name('track');
    Route::get('visitor', [TrackingController::class, 'visitor'])->name('visitor');

    // API routes are disabled until controllers are implemented
    // Uncomment these routes after creating the corresponding controllers

    // // Route::post('fingerprint', 'Api\TrackingController@fingerprint')->name('fingerprint');
    // // Route::post('attribute', 'Api\TrackingController@setAttribute')->name('set-attribute');

    // // Condition evaluation
    // // Route::post('evaluate', 'Api\ConditionController@evaluate')->name('evaluate');

    // // External API data
    // // Route::get('external/{source}', 'Api\ExternalController@fetch')->name('external');

    // // Webhooks
    // // Route::post('webhook/{connection}', 'Api\WebhookController@handle')->name('webhook');
});
