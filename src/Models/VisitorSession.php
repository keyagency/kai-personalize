<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class VisitorSession extends Model
{
    protected $table = 'kai_personalize_visitor_sessions';

    protected $fillable = [
        'visitor_id',
        'session_id',
        'ip_address',
        'user_agent',
        'started_at',
        'ended_at',
        'page_views',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'page_views' => 'integer',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class, 'session_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'session_id');
    }

    /**
     * Get decrypted IP address
     */
    public function getIpAddressAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return config('kai-personalize.privacy.encrypt_ip')
                ? Crypt::decryptString($value)
                : $value;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted IP address
     */
    public function setIpAddressAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['ip_address'] = null;

            return;
        }

        $this->attributes['ip_address'] = config('kai-personalize.privacy.encrypt_ip')
            ? Crypt::encryptString($value)
            : $value;
    }

    /**
     * Create or update session
     */
    public static function createOrUpdate(int $visitorId, string $sessionId, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        $session = static::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'visitor_id' => $visitorId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'started_at' => now(),
                'page_views' => 1,
            ]
        );

        if (! $session->wasRecentlyCreated) {
            $session->increment('page_views');
            $session->touch();
        }

        return $session;
    }

    /**
     * End the session
     */
    public function end(): bool
    {
        return $this->update(['ended_at' => now()]);
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }
}
