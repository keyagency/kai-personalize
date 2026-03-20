<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class SettingsController extends CpController
{
    public function index()
    {
        return view('kai-personalize::settings.index', [
            'title' => __('kai-personalize::messages.settings.title'),
            'config' => config('kai-personalize'),
            'version' => \KeyAgency\KaiPersonalize\ServiceProvider::version(),
            'edition' => \KeyAgency\KaiPersonalize\Edition::get(),
        ]);
    }

    public function update(Request $request)
    {
        // For now, just flash a success message
        // Full implementation would save to config file
        return back()->with('success', __('kai-personalize::messages.settings.saved'));
    }
}
