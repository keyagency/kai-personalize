<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Carbon\Carbon;
use KeyAgency\KaiPersonalize\Edition;
use KeyAgency\KaiPersonalize\Models\Event;
use KeyAgency\KaiPersonalize\Models\PageView;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class PageAnalyticsController extends CpController
{
    public function __construct()
    {
        // Block access to analytics in lite edition
        if (Edition::isLite()) {
            abort(403, __('kai-personalize::messages.analytics.pro_feature'));
        }
    }

    public function index()
    {
        return view('kai-personalize::analytics.pages', [
            'title' => __('kai-personalize::messages.analytics.pages.title'),
        ]);
    }

    public function data(Request $request)
    {
        $pages = $this->getPageAnalytics();

        return response()->json([
            'pages' => $pages->items(),
            'pagination' => [
                'current_page' => $pages->currentPage(),
                'total' => $pages->total(),
                'per_page' => $pages->perPage(),
                'links' => $pages->toArray()['links'] ?? [],
            ],
        ]);
    }

    public function showBySlug($slug)
    {
        // Find the page URL by entry_slug
        $pageView = PageView::where('entry_slug', $slug)->first();

        if (!$pageView) {
            // Fall back to query parameter for non-entry pages
            return $this->show();
        }

        $urlPath = $pageView->url_path;

        return $this->renderPageDetail($urlPath);
    }

    public function show()
    {
        // Get URL path from query parameter
        $urlPath = request()->query('path', '/');

        // Ensure path has leading slash
        $urlPath = '/' . ltrim($urlPath, '/');

        return $this->renderPageDetail($urlPath);
    }

    protected function renderPageDetail($urlPath)
    {
        // Get page statistics with defaults
        try {
            $stats = array_merge([
                'total_views' => 0,
                'unique_visitors' => 0,
                'avg_scroll_depth' => 0,
                'avg_reading_time_ms' => 0,
            ], $this->getPageStats($urlPath) ?? []);
        } catch (\Exception $e) {
            $stats = [
                'total_views' => 0,
                'unique_visitors' => 0,
                'avg_scroll_depth' => 0,
                'avg_reading_time_ms' => 0,
            ];
        }

        // Get recent views for this page
        try {
            $recentViews = PageView::where('url_path', $urlPath)
                ->with('visitor')
                ->orderByDesc('viewed_at')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            $recentViews = collect();
        }

        return view('kai-personalize::analytics.page-detail', [
            'title' => __('kai-personalize::messages.analytics.pages.title').': '.$urlPath,
            'urlPath' => $urlPath,
            'stats' => $stats,
            'recentViews' => $recentViews,
        ]);
    }

    protected function getPageStats(string $urlPath): array
    {
        return [
            'total_views' => PageView::where('url_path', $urlPath)->count(),
            'unique_visitors' => PageView::where('url_path', $urlPath)->distinct('visitor_id')->count('visitor_id'),
            'avg_scroll_depth' => $this->getAvgScrollDepth($urlPath),
            'avg_reading_time_ms' => $this->getAvgReadingTime($urlPath),
        ];
    }

    protected function getPageAnalytics()
    {
        $paginator = PageView::selectRaw('
            url_path,
            entry_slug,
            entry_title,
            collection_handle,
            COUNT(*) as views,
            COUNT(DISTINCT visitor_id) as unique_visitors,
            MIN(viewed_at) as first_view,
            MAX(viewed_at) as last_view
        ')
            ->groupBy('url_path', 'entry_slug', 'entry_title', 'collection_handle')
            ->orderByDesc('views')
            ->paginate(50);

        // Transform the collection to cast date strings to Carbon instances and format them
        $paginator->getCollection()->transform(function ($item) {
            $item->first_view = $item->first_view ? Carbon::parse($item->first_view)->format('M d, Y H:i') : null;
            $item->last_view = $item->last_view ? Carbon::parse($item->last_view)->diffForHumans() : null;

            return $item;
        });

        return $paginator;
    }

    protected function getAvgScrollDepth(string $urlPath): float
    {
        return Event::where('event_type', 'scroll_depth')
            ->where('event_data->url', $urlPath)
            ->get()
            ->avg(fn ($event) => $event->event_data['max_depth'] ?? 0) ?? 0;
    }

    protected function getAvgReadingTime(string $urlPath): int
    {
        return (int) Event::where('event_type', 'reading_time')
            ->where('event_data->url', $urlPath)
            ->get()
            ->avg(fn ($event) => $event->event_data['duration_ms'] ?? 0);
    }
}
