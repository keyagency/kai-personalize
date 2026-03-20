<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ApiConnection extends Model
{
    protected $table = 'kai_personalize_api_connections';

    protected $fillable = [
        'name',
        'provider',
        'api_url',
        'api_key',
        'auth_type',
        'auth_config',
        'headers',
        'rate_limit',
        'timeout',
        'is_active',
        'cache_duration',
        'last_used_at',
    ];

    protected $casts = [
        'auth_config' => 'json',
        'headers' => 'json',
        'rate_limit' => 'integer',
        'timeout' => 'integer',
        'is_active' => 'boolean',
        'cache_duration' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function cacheEntries(): HasMany
    {
        return $this->hasMany(ApiCache::class, 'connection_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class, 'connection_id');
    }

    /**
     * Get decrypted API key
     */
    public function getApiKeyAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted API key
     */
    public function setApiKeyAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['api_key'] = null;

            return;
        }

        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Scope to get active connections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get connections by provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * Clear cache for this connection
     */
    public function clearCache(): int
    {
        return $this->cacheEntries()->delete();
    }

    /**
     * Get recent logs
     */
    public function recentLogs(int $limit = 10)
    {
        return $this->logs()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
