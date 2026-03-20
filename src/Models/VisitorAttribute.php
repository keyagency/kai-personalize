<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorAttribute extends Model
{
    protected $table = 'kai_personalize_visitor_attributes';

    protected $fillable = [
        'visitor_id',
        'session_id',
        'attribute_key',
        'attribute_value',
        'attribute_type',
        'expires_at',
    ];

    protected $casts = [
        'attribute_value' => 'json',
        'expires_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class);
    }

    /**
     * Scope to get non-expired attributes
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get attributes by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('attribute_type', $type);
    }

    /**
     * Check if attribute is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if attribute is active
     */
    public function isActive(): bool
    {
        return ! $this->isExpired();
    }
}
