<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlacklistLog extends Model
{
    protected $table = 'kai_personalize_blacklist_logs';

    protected $fillable = [
        'blacklist_id',
        'bot_name',
        'user_agent',
        'ip_address',
        'url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function blacklist(): BelongsTo
    {
        return $this->belongsTo(Blacklist::class, 'blacklist_id');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
