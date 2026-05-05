<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use KeyAgency\KaiPersonalize\Models\Blacklist;
use KeyAgency\KaiPersonalize\Models\BlacklistLog;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class BlacklistController extends CpController
{
    public function index()
    {
        $blacklists = Blacklist::withCount('logs')
            ->orderBy('hit_count', 'desc')
            ->get();

        return view('kai-personalize::blacklists.index', [
            'title' => __('kai-personalize::messages.blacklists.title'),
            'blacklists' => $blacklists,
        ]);
    }

    public function create()
    {
        return view('kai-personalize::blacklists.create', [
            'title' => __('kai-personalize::messages.blacklists.create'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bot_name,user_agent',
            'pattern' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Blacklist::create($validated);

        return redirect()->route('statamic.cp.kai-personalize.blacklists.index')
            ->with('success', __('kai-personalize::messages.blacklists.created'));
    }

    public function edit(Blacklist $blacklist)
    {
        return view('kai-personalize::blacklists.edit', [
            'title' => __('kai-personalize::messages.blacklists.edit'),
            'blacklist' => $blacklist,
        ]);
    }

    public function update(Request $request, Blacklist $blacklist)
    {
        $validated = $request->validate([
            'type' => 'required|in:bot_name,user_agent',
            'pattern' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $blacklist->update($validated);

        return redirect()->route('statamic.cp.kai-personalize.blacklists.index')
            ->with('success', __('kai-personalize::messages.blacklists.updated'));
    }

    public function destroy(Blacklist $blacklist)
    {
        $blacklist->delete();

        return redirect()->route('statamic.cp.kai-personalize.blacklists.index')
            ->with('success', __('kai-personalize::messages.blacklists.deleted'));
    }

    public function toggle(Blacklist $blacklist)
    {
        $blacklist->update(['is_active' => !$blacklist->is_active]);

        return back();
    }

    public function logs(Blacklist $blacklist)
    {
        $logs = $blacklist->logs()
            ->recent(168)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('kai-personalize::blacklists.logs', [
            'title' => __('kai-personalize::messages.blacklists.logs'),
            'blacklist' => $blacklist,
            'logs' => $logs,
        ]);
    }
}
