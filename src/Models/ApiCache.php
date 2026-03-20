<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCache extends Model
{
    protected $table = 'kai_personalize_api_cache';

    protected $fillable = [
        'connection_id',
        'cache_key',
        'request_params',
        'response_data',
        'expires_at',
    ];

    protected $casts = [
        'request_params' => 'json',
        'response_data' => 'json',
        'expires_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    /**
     * Scope to get non-expired cache entries
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Check if cache entry is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get cached data by key and connection
     */
    public static function getCached(int $connectionId, string $cacheKey): ?array
    {
        $cache = static::where('connection_id', $connectionId)
            ->where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        return $cache ? $cache->response_data : null;
    }

    /**
     * Store data in cache
     */
    public static function store(int $connectionId, string $cacheKey, array $requestParams, array $responseData, int $ttl): self
    {
        return static::updateOrCreate(
            [
                'connection_id' => $connectionId,
                'cache_key' => $cacheKey,
            ],
            [
                'request_params' => $requestParams,
                'response_data' => $responseData,
                'expires_at' => now()->addSeconds($ttl),
            ]
        );
    }

    /**
     * Clear expired cache entries
     */
    public static function clearExpired(): int
    {
        return static::where('expires_at', '<=', now())->delete();
    }
}
