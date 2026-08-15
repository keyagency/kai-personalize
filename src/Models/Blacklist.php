<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Blacklist extends Model
{
    protected $table = 'kai_personalize_blacklists';

    protected $fillable = [
        'type',
        'pattern',
        'description',
        'is_active',
        'hit_count',
        'last_hit_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_hit_at' => 'datetime',
    ];

    /**
     * Keys cached by BlacklistService::activePatterns().
     */
    public const PATTERN_CACHE_KEYS = [
        'kai:blacklist:patterns:bot_name',
        'kai:blacklist:patterns:user_agent',
    ];

    protected static function booted(): void
    {
        // Only a change to what we match on invalidates the cache — hit counters
        // are written on every blocked request and must not flush it.
        static::saved(function (self $blacklist) {
            if ($blacklist->wasChanged(['type', 'pattern', 'is_active']) || $blacklist->wasRecentlyCreated) {
                static::forgetPatternCache();
            }
        });

        static::deleted(fn () => static::forgetPatternCache());
    }

    public static function forgetPatternCache(): void
    {
        foreach (self::PATTERN_CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BlacklistLog::class, 'blacklist_id');
    }

    public function incrementHit(): void
    {
        $this->increment('hit_count');
        $this->update(['last_hit_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
