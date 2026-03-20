<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'kai_personalize_logs';

    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'rule_id',
        'matched_conditions',
        'content_shown',
        'created_at',
    ];

    protected $casts = [
        'matched_conditions' => 'json',
        'content_shown' => 'json',
        'created_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    /**
     * Create a new log entry
     */
    public static function createEntry(int $visitorId, ?int $ruleId = null, ?array $matchedConditions = null, ?array $contentShown = null): self
    {
        return static::create([
            'visitor_id' => $visitorId,
            'rule_id' => $ruleId,
            'matched_conditions' => $matchedConditions,
            'content_shown' => $contentShown,
            'created_at' => now(),
        ]);
    }
}
