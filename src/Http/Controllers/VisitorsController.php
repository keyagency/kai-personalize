<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Illuminate\Http\Request;
use KeyAgency\KaiPersonalize\Models\Visitor;
use Statamic\Http\Controllers\CP\CpController;

class VisitorsController extends CpController
{
    public function index(Request $request)
    {
        $query = Visitor::with(['sessions' => function ($query) {
            $query->latest()->limit(1);
        }]);

        // Filter by returning visitors
        if ($request->filled('returning')) {
            $query->where('visit_count', '>', 1);
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('first_visit_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('first_visit_at', '<=', $request->to);
        }

        // Search by fingerprint
        if ($request->filled('search')) {
            $query->where('fingerprint_hash', 'like', '%'.$request->search.'%');
        }

        $visitors = $query->orderBy('last_visit_at', 'desc')
            ->paginate(50);

        return view('kai-personalize::visitors.index', [
            'title' => __('kai-personalize::messages.visitors.title'),
            'visitors' => $visitors,
            'filters' => $request->all(),
        ]);
    }

    public function show($id)
    {
        $visitor = Visitor::with(['sessions', 'attributes', 'logs', 'pageViews', 'events'])
            ->findOrFail($id);

        $stats = [
            'engagement_score' => $visitor->engagementScore(),
            'total_sessions' => $visitor->sessions()->count(),
            'total_page_views' => $visitor->pageViews()->count(),
            'total_attributes' => $visitor->attributes()->count(),
            'total_rule_matches' => $visitor->logs()->count(),
            'first_visit' => $visitor->first_visit_at,
            'last_visit' => $visitor->last_visit_at,
            'returning' => $visitor->visit_count > 1,
        ];

        $recentSessions = $visitor->sessions()
            ->latest('started_at')
            ->limit(10)
            ->get();

        $attributes = $visitor->attributes()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        // Get pageview history
        $pageHistory = $visitor->pageViews()
            ->orderBy('viewed_at', 'desc')
            ->paginate(20);

        // Get behavioral summary
        $behaviorSummary = $visitor->behavioralSummary();

        return view('kai-personalize::visitors.show', [
            'title' => 'Visitor: '.substr($visitor->fingerprint_hash, 0, 16).'...',
            'visitor' => $visitor,
            'stats' => $stats,
            'recentSessions' => $recentSessions,
            'attributes' => $attributes,
            'pageHistory' => $pageHistory,
            'behaviorSummary' => $behaviorSummary,
        ]);
    }

    public function destroy($id)
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->delete(); // Cascade delete will handle sessions, attributes, logs

        return redirect()
            ->route('statamic.cp.kai-personalize.visitors.index')
            ->with('success', __('kai-personalize::messages.visitors.deleted'));
    }
}
