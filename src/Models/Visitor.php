<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    protected $table = 'kai_personalize_visitors';

    protected $fillable = [
        'fingerprint_hash',
        'session_id',
        'first_visit_at',
        'last_visit_at',
        'visit_count',
    ];

    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'visit_count' => 'integer',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(VisitorAttribute::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(Segment::class, 'kai_personalize_segment_visitor')
            ->withTimestamps()
            ->withPivot('assigned_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get visitor by fingerprint hash
     */
    public static function findByFingerprint(string $fingerprint): ?self
    {
        return static::where('fingerprint_hash', $fingerprint)->first();
    }

    /**
     * Create or update visitor
     */
    public static function createOrUpdate(string $fingerprint, ?string $sessionId = null): self
    {
        $visitor = static::firstOrCreate(
            ['fingerprint_hash' => $fingerprint],
            [
                'session_id' => $sessionId,
                'first_visit_at' => now(),
                'last_visit_at' => now(),
                'visit_count' => 1,
            ]
        );

        if (! $visitor->wasRecentlyCreated) {
            $visitor->update([
                'last_visit_at' => now(),
                'visit_count' => $visitor->visit_count + 1,
            ]);
        }

        return $visitor;
    }

    /**
     * Get visitor attribute value
     */
    public function getVisitorAttribute(string $key, $default = null, ?int $sessionId = null)
    {
        $query = $this->attributes()
            ->where('attribute_key', $key)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($sessionId !== null) {
            $query->where('session_id', $sessionId);
        }

        $attribute = $query->latest()->first();

        return $attribute ? $attribute->attribute_value : $default;
    }

    /**
     * Set visitor attribute value
     */
    public function setVisitorAttribute(string $key, $value, ?string $type = 'personal', ?\DateTimeInterface $expiresAt = null, ?int $sessionId = null): VisitorAttribute
    {
        $matchCriteria = ['attribute_key' => $key];

        if ($sessionId !== null) {
            $matchCriteria['session_id'] = $sessionId;
        }

        return $this->attributes()->updateOrCreate(
            $matchCriteria,
            [
                'attribute_value' => $value,
                'attribute_type' => $type,
                'expires_at' => $expiresAt,
                'session_id' => $sessionId,
            ]
        );
    }

    /**
     * Calculate engagement score for this visitor
     * Returns a score from 0-100 based on:
     * - Visit frequency (0-30 points)
     * - Page views (0-25 points)
     * - Reading time (0-25 points)
     * - Scroll depth (0-20 points)
     */
    public function engagementScore(): int
    {
        $score = 0;

        // Visit frequency (0-30 points)
        $score += min($this->visit_count * 3, 30);

        // Page views (0-25 points)
        $pageViews = $this->pageViews()->count();
        $score += min($pageViews * 2, 25);

        // Reading time from events (0-25 points)
        $readingTime = $this->events()
            ->where('event_type', 'reading_time')
            ->get()
            ->sum(fn ($event) => $event->event_data['duration_ms'] ?? 0);
        $score += min(intval($readingTime / 10000), 25);

        // Scroll depth (0-20 points)
        $maxScroll = $this->events()
            ->where('event_type', 'scroll_depth')
            ->get()
            ->max(fn ($event) => $event->event_data['max_depth'] ?? 0);
        $score += min(intval($maxScroll / 5), 20);

        return min(intval($score), 100);
    }

    /**
     * Get behavioral summary data
     */
    public function behavioralSummary(): array
    {
        $scrollEvents = $this->events()
            ->where('event_type', 'scroll_depth')
            ->get();

        $readingTimeEvents = $this->events()
            ->where('event_type', 'reading_time')
            ->get();

        return [
            'max_scroll_depth' => $scrollEvents->max(fn ($event) => $event->event_data['max_depth'] ?? 0) ?? 0,
            'total_reading_time_ms' => $readingTimeEvents->sum(fn ($event) => $event->event_data['duration_ms'] ?? 0),
            'total_clicks' => $this->events()->where('event_type', 'click')->count(),
            'total_events' => $this->events()->count(),
        ];
    }
}
