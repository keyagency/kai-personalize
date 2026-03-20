<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\Event;
use KeyAgency\KaiPersonalize\Models\Visitor;
use Statamic\Tags\Tags;

class KaiBehavior extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

    /**
     * {{ kai:behavior }}
     * Returns current visitor's behavioral data
     */
    public function behavior(): array
    {
        $visitor = $this->getCurrentVisitor();

        if (! $visitor) {
            return $this->getEmptyBehavior();
        }

        $currentSession = $visitor->sessions()->latest()->first();

        if (! $currentSession) {
            return $this->getEmptyBehavior();
        }

        // Get aggregated behavioral data from events
        $scrollData = $this->getScrollBehavior($visitor->id);
        $clickData = $this->getClickBehavior($visitor->id);
        $readingTime = $this->getTotalReadingTime($visitor->id);
        $pageViews = $visitor->pageViews()->count();

        return [
            'scroll_depth' => $scrollData['max_depth'] ?? 0,
            'scroll_thresholds_reached' => $scrollData['thresholds'] ?? [],
            'total_clicks' => $clickData['total'] ?? 0,
            'rage_clicks' => $clickData['rage'] ?? 0,
            'dead_clicks' => $clickData['dead'] ?? 0,
            'active_time_seconds' => round($readingTime / 1000),
            'active_time_ms' => $readingTime,
            'page_views' => $pageViews,
            'is_returning' => $visitor->visit_count > 1,
            'visit_count' => $visitor->visit_count,
            'has_scrolled_deep' => ($scrollData['max_depth'] ?? 0) >= 75,
            'is_engaged' => $readingTime > 30000, // 30+ seconds
            'last_activity' => $this->getLastActivityTime($visitor->id),
            'device_type' => $visitor->getVisitorAttribute('device_type') ?? 'unknown',
            'timezone' => $visitor->getVisitorAttribute('timezone'),
        ];
    }

    /**
     * {{ kai:if:scroll_depth operator=">=" value="75" }}
     * Conditional check against behavioral data
     */
    public function ifScrollDepth(): bool
    {
        $operator = $this->params->get('operator', '>=');
        $value = $this->params->get('value', 0);

        $visitor = $this->getCurrentVisitor();
        if (! $visitor) {
            return false;
        }

        $scrollData = $this->getScrollBehavior($visitor->id);
        $maxDepth = $scrollData['max_depth'] ?? 0;

        return $this->evaluateCondition($maxDepth, $operator, $value);
    }

    /**
     * {{ kai:if:active_time operator=">" value="30" }}
     * Check if active time exceeds threshold (in seconds)
     */
    public function ifActiveTime(): bool
    {
        $operator = $this->params->get('operator', '>');
        $value = $this->params->int('value', 0);

        $visitor = $this->getCurrentVisitor();
        if (! $visitor) {
            return false;
        }

        $readingTime = $this->getTotalReadingTime($visitor->id);
        $activeSeconds = round($readingTime / 1000);

        return $this->evaluateCondition($activeSeconds, $operator, $value);
    }

    /**
     * {{ kai:if:page_views operator=">=" value="3" }}
     * Check if visitor has viewed minimum pages
     */
    public function ifPageViews(): bool
    {
        $operator = $this->params->get('operator', '>=');
        $value = $this->params->int('value', 0);

        $visitor = $this->getCurrentVisitor();
        if (! $visitor) {
            return false;
        }

        $pageViews = $visitor->pageViews()->count();

        return $this->evaluateCondition($pageViews, $operator, $value);
    }

    /**
     * {{ kai:if:visited url="/about" }}
     * Check if visitor has viewed a specific page
     */
    public function ifVisited(): bool
    {
        $url = $this->params->get('url');
        $collection = $this->params->get('collection');

        if (! $url && ! $collection) {
            return false;
        }

        $visitor = $this->getCurrentVisitor();
        if (! $visitor) {
            return false;
        }

        $query = $visitor->pageViews();

        if ($url) {
            $query->where('url_path', $url);
        }

        if ($collection) {
            $query->where('collection_handle', $collection);
        }

        return $query->exists();
    }

    /**
     * {{ kai:events type="scroll_depth" limit="10" }}
     * Get raw events of a specific type
     */
    public function events(): array
    {
        $visitor = $this->getCurrentVisitor();
        if (! $visitor) {
            return [];
        }

        $type = $this->params->get('type');
        $limit = $this->params->int('limit', 10);

        $query = $visitor->events()->orderByDesc('created_at');

        if ($type) {
            $query->where('event_type', $type);
        }

        return $query->limit($limit)->get()->map(function ($event) {
            return array_merge($event->event_data, [
                'type' => $event->event_type,
                'created_at' => $event->created_at,
            ]);
        })->toArray();
    }

    /**
     * {{ kai:sections_viewed }}
     * Returns which sections of the page were viewed (Intersection Observer data)
     */
    public function sectionsViewed(): array
    {
        $visitor = $this->getCurrentVisitor();
        if (! $visitor) {
            return [];
        }

        $events = $visitor->events()
            ->where('event_type', 'section_view')
            ->orderByDesc('created_at')
            ->get();

        $sections = [];
        foreach ($events as $event) {
            $sectionId = $event->event_data['section_id'] ?? null;
            if ($sectionId) {
                $sections[$sectionId] = [
                    'id' => $sectionId,
                    'view_count' => ($sections[$sectionId]['view_count'] ?? 0) + 1,
                    'total_duration_ms' => ($sections[$sectionId]['total_duration_ms'] ?? 0) +
                        ($event->event_data['duration_ms'] ?? 0),
                    'last_viewed' => $event->created_at,
                ];
            }
        }

        return array_values($sections);
    }

    protected function getCurrentVisitor(): ?Visitor
    {
        $visitorId = Session::get(config('kai-personalize.session.visitor_id_key'));

        if (! $visitorId) {
            return null;
        }

        return Visitor::where('fingerprint_hash', $visitorId)->first();
    }

    protected function getEmptyBehavior(): array
    {
        return [
            'scroll_depth' => 0,
            'scroll_thresholds_reached' => [],
            'total_clicks' => 0,
            'rage_clicks' => 0,
            'dead_clicks' => 0,
            'active_time_seconds' => 0,
            'active_time_ms' => 0,
            'page_views' => 0,
            'is_returning' => false,
            'visit_count' => 0,
            'has_scrolled_deep' => false,
            'is_engaged' => false,
            'last_activity' => null,
            'device_type' => 'unknown',
            'timezone' => null,
        ];
    }

    protected function getScrollBehavior(int $visitorId): array
    {
        $events = Event::where('visitor_id', $visitorId)
            ->where('event_type', 'scroll_depth')
            ->get();

        $thresholds = [];
        $maxDepth = 0;

        foreach ($events as $event) {
            $threshold = $event->event_data['threshold'] ?? null;
            if ($threshold !== null) {
                $thresholds[] = $threshold;
            }
            $depth = $event->event_data['max_depth'] ?? 0;
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }

        return [
            'max_depth' => $maxDepth,
            'thresholds' => array_unique($thresholds),
        ];
    }

    protected function getClickBehavior(int $visitorId): array
    {
        $clickEvents = Event::where('visitor_id', $visitorId)
            ->where('event_type', 'click')
            ->count();

        $rageEvents = Event::where('visitor_id', $visitorId)
            ->where('event_type', 'rage_click')
            ->count();

        $deadEvents = Event::where('visitor_id', $visitorId)
            ->where('event_type', 'dead_click')
            ->count();

        return [
            'total' => $clickEvents,
            'rage' => $rageEvents,
            'dead' => $deadEvents,
        ];
    }

    protected function getTotalReadingTime(int $visitorId): int
    {
        $events = Event::where('visitor_id', $visitorId)
            ->where('event_type', 'reading_time')
            ->get();

        $total = 0;
        foreach ($events as $event) {
            $total += $event->event_data['duration_ms'] ?? 0;
        }

        return $total;
    }

    protected function getLastActivityTime(int $visitorId): ?string
    {
        $event = $visitor->events()->orderByDesc('created_at')->first();

        return $event?->created_at?->toIso8601String();
    }

    protected function evaluateCondition($actualValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            'equals', '==' => $actualValue == $expectedValue,
            'not_equals', '!=' => $actualValue != $expectedValue,
            'greater_than', '>' => $actualValue > $expectedValue,
            'less_than', '<' => $actualValue < $expectedValue,
            'greater_or_equal', '>=' => $actualValue >= $expectedValue,
            'less_or_equal', '<=' => $actualValue <= $expectedValue,
            'in' => in_array($actualValue, (array) $expectedValue),
            'not_in' => ! in_array($actualValue, (array) $expectedValue),
            default => false,
        };
    }
}
