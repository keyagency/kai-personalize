<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    protected $table = 'kai_personalize_page_views';

    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'session_id',
        'url_path',
        'entry_slug',
        'entry_title',
        'collection_handle',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'session_id');
    }

    public static function createView(
        int $visitorId,
        int $sessionId,
        string $urlPath,
        ?string $entrySlug = null,
        ?string $entryTitle = null,
        ?string $collectionHandle = null
    ): self {
        return static::create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'url_path' => $urlPath,
            'entry_slug' => $entrySlug,
            'entry_title' => $entryTitle,
            'collection_handle' => $collectionHandle,
            'viewed_at' => now(),
        ]);
    }

    public function scopeForCollection($query, string $handle)
    {
        return $query->where('collection_handle', $handle);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('viewed_at', '>=', now()->subMinutes($minutes));
    }
}
