<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Carbon\Carbon;
use KeyAgency\KaiPersonalize\Models\ApiConnection;
use KeyAgency\KaiPersonalize\Models\Log;
use KeyAgency\KaiPersonalize\Models\PageView;
use KeyAgency\KaiPersonalize\Models\Rule;
use KeyAgency\KaiPersonalize\Models\Visitor;
use KeyAgency\KaiPersonalize\Models\VisitorSession;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class DashboardController extends CpController
{
    public function index()
    {
        return view('kai-personalize::dashboard.index', [
            'title' => __('kai-personalize::messages.dashboard.title'),
        ]);
    }

    public function data(Request $request)
    {
        return response()->json([
            'stats' => $this->getStatistics(),
            'recentVisitors' => $this->getRecentVisitors(),
            'topPages' => $this->getTopPages(),
            'recentSessions' => $this->getRecentSessions(),
            'topEngagedVisitors' => $this->getTopEngagedVisitors(),
        ]);
    }

    protected function getStatistics()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();
        $lastWeek = $now->copy()->subWeek();
        $lastMonth = $now->copy()->subMonth();

        return [
            'total_visitors' => Visitor::count(),
            'new_today' => Visitor::where('first_visit_at', '>=', $today)->count(),
            'new_yesterday' => Visitor::whereBetween('first_visit_at', [$yesterday, $today])->count(),
            'new_this_week' => Visitor::where('first_visit_at', '>=', $lastWeek)->count(),
            'new_this_month' => Visitor::where('first_visit_at', '>=', $lastMonth)->count(),

            'total_sessions' => VisitorSession::count(),
            'active_sessions' => VisitorSession::where('updated_at', '>=', $now->copy()->subMinutes(30))
                ->whereNull('ended_at')
                ->count(),
            'sessions_today' => VisitorSession::where('started_at', '>=', $today)->count(),

            'total_rules' => Rule::count(),
            'active_rules' => Rule::where('is_active', true)->count(),

            'total_api_connections' => ApiConnection::count(),
            'active_api_connections' => ApiConnection::where('is_active', true)->count(),

            'total_rule_matches' => Log::count(),
            'rule_matches_today' => Log::where('created_at', '>=', $today)->count(),
        ];
    }

    protected function getRecentVisitors($limit = 10)
    {
        return Visitor::orderBy('last_visit_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($visitor) {
                return [
                    'id' => $visitor->id,
                    'fingerprint' => substr($visitor->fingerprint_hash, 0, 8).'...',
                    'last_seen' => $visitor->last_visit_at?->diffForHumans(),
                    'first_seen' => $visitor->first_visit_at?->diffForHumans(),
                    'sessions_count' => $visitor->visit_count,
                    'is_returning' => $visitor->visit_count > 1,
                ];
            });
    }

    protected function getTopPages($limit = 10)
    {
        return PageView::selectRaw('
            url_path,
            entry_title,
            entry_slug,
            collection_handle,
            COUNT(*) as views,
            COUNT(DISTINCT visitor_id) as unique_visitors,
            MIN(viewed_at) as first_view,
            MAX(viewed_at) as last_view
        ')
            ->groupBy('url_path', 'entry_title', 'entry_slug', 'collection_handle')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->entry_title ?: $item->url_path,
                    'url_path' => $item->url_path,
                    'entry_slug' => $item->entry_slug,
                    'views' => $item->views,
                    'unique_visitors' => $item->unique_visitors,
                ];
            });
    }

    protected function getRecentSessions($limit = 10)
    {
        return VisitorSession::with('visitor')
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($session) {
                $lastActivity = $session->ended_at ?? $session->updated_at;

                return [
                    'id' => $session->id,
                    'visitor_id' => $session->visitor_id,
                    'started' => $session->started_at?->diffForHumans(),
                    'last_activity' => $lastActivity?->diffForHumans(),
                    'pages_viewed' => $session->page_views,
                    'duration' => $session->started_at && $lastActivity
                        ? $session->started_at->diffInMinutes($lastActivity).' min'
                        : 'N/A',
                ];
            });
    }

    /**
     * Get top engaged visitors ordered by engagement score
     */
    protected function getTopEngagedVisitors($limit = 10)
    {
        return Visitor::withCount(['pageViews', 'events'])
            ->get()
            ->sortByDesc(fn ($visitor) => $visitor->engagementScore())
            ->take($limit)
            ->map(function ($visitor) {
                return [
                    'id' => $visitor->id,
                    'fingerprint' => substr($visitor->fingerprint_hash, 0, 8).'...',
                    'score' => $visitor->engagementScore(),
                    'page_views' => $visitor->page_views_count ?? 0,
                    'sessions' => $visitor->visit_count,
                    'last_seen' => $visitor->last_visit_at?->diffForHumans(),
                ];
            })->values();
    }
}
