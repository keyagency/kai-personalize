<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Illuminate\Http\Request;
use KeyAgency\KaiPersonalize\Edition;
use KeyAgency\KaiPersonalize\Models\Segment;
use Statamic\Http\Controllers\CP\CpController;

class SegmentsController extends CpController
{
    public function __construct()
    {
        // Block access to segments in lite edition
        if (Edition::isLite()) {
            abort(403, __('kai-personalize::messages.segments.pro_feature'));
        }
    }

    public function index()
    {
        $segments = Segment::orderBy('name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('kai-personalize::segments.index', [
            'title' => __('kai-personalize::messages.segments.title'),
            'segments' => $segments,
        ]);
    }

    public function create()
    {
        return view('kai-personalize::segments.create', [
            'title' => __('kai-personalize::messages.segments.create'),
            'segment' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'criteria' => 'required|json',
            'is_active' => 'boolean',
        ]);

        // Decode criteria JSON
        $validated['criteria'] = json_decode($validated['criteria'], true);
        $validated['is_active'] = $request->has('is_active');
        $validated['visitor_count'] = 0;

        $segment = Segment::create($validated);

        return redirect()
            ->route('statamic.cp.kai-personalize.segments.index')
            ->with('success', __('kai-personalize::messages.segments.created'));
    }

    public function show($id)
    {
        $segment = Segment::with(['visitors' => function ($query) {
            $query->latest('kai_personalize_segment_visitor.assigned_at')->limit(20);
        }])->findOrFail($id);

        $stats = [
            'total_visitors' => $segment->visitor_count,
            'active_visitors' => $segment->visitors()
                ->where('last_visit_at', '>=', now()->subDays(30))
                ->count(),
            'new_today' => $segment->visitors()
                ->wherePivot('assigned_at', '>=', now()->startOfDay())
                ->count(),
        ];

        return view('kai-personalize::segments.show', [
            'title' => $segment->name,
            'segment' => $segment,
            'stats' => $stats,
            'recentVisitors' => $segment->visitors()->take(20)->get(),
        ]);
    }

    public function edit($id)
    {
        $segment = Segment::findOrFail($id);

        return view('kai-personalize::segments.edit', [
            'title' => __('kai-personalize::messages.segments.edit'),
            'segment' => $segment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $segment = Segment::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'criteria' => 'required|json',
            'is_active' => 'boolean',
        ]);

        // Decode criteria JSON
        $validated['criteria'] = json_decode($validated['criteria'], true);
        $validated['is_active'] = $request->has('is_active');

        $segment->update($validated);

        return redirect()
            ->route('statamic.cp.kai-personalize.segments.show', $segment->id)
            ->with('success', __('kai-personalize::messages.segments.updated'));
    }

    public function destroy($id)
    {
        $segment = Segment::findOrFail($id);
        $segment->delete();

        return redirect()
            ->route('statamic.cp.kai-personalize.segments.index')
            ->with('success', __('kai-personalize::messages.segments.deleted'));
    }

    /**
     * Refresh segment by re-evaluating all visitors
     */
    public function refresh($id)
    {
        $segment = Segment::findOrFail($id);

        // Clear existing visitors
        $segment->visitors()->detach();

        // Re-assign matching visitors
        $assigned = $segment->assignMatchingVisitors();

        // Refresh count
        $segment->refreshVisitorCount();

        return back()->with('success', "Refreshed segment. {$assigned} visitors assigned.");
    }
}
