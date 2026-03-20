<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $table = 'kai_personalize_events';

    protected $fillable = [
        'visitor_id',
        'session_id',
        'event_type',
        'event_data',
        'created_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'session_id');
    }

    /**
     * Create a new event for a visitor
     */
    public static function createEvent(int $visitorId, int $sessionId, string $eventType, array $eventData): self
    {
        return static::create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'created_at' => now(),
        ]);
    }

    /**
     * Get aggregated data for a visitor by event type
     */
    public static function getAggregatedForVisitor(int $visitorId, string $eventType): array
    {
        return static::where('visitor_id', $visitorId)
            ->where('event_type', $eventType)
            ->orderBy('created_at')
            ->get()
            ->map(function ($event) {
                return array_merge($event->event_data, [
                    'created_at' => $event->created_at,
                ]);
            })
            ->toArray();
    }

    /**
     * Get the latest event of a specific type for a visitor
     */
    public static function getLatestForVisitor(int $visitorId, string $eventType): ?self
    {
        return static::where('visitor_id', $visitorId)
            ->where('event_type', $eventType)
            ->orderByDesc('created_at')
            ->first();
    }
}
