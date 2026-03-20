<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    protected $table = 'kai_personalize_api_logs';

    public $timestamps = false;

    protected $fillable = [
        'connection_id',
        'request_url',
        'request_method',
        'request_params',
        'response_status',
        'response_data',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'request_params' => 'json',
        'response_data' => 'json',
        'response_status' => 'integer',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    /**
     * Create a new log entry
     */
    public static function createEntry(
        int $connectionId,
        string $requestUrl,
        string $method,
        ?array $params,
        ?int $status,
        ?array $response,
        ?string $error,
        int $duration
    ): self {
        return static::create([
            'connection_id' => $connectionId,
            'request_url' => $requestUrl,
            'request_method' => $method,
            'request_params' => $params,
            'response_status' => $status,
            'response_data' => $response,
            'error_message' => $error,
            'duration_ms' => $duration,
            'created_at' => now(),
        ]);
    }

    /**
     * Scope to get failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('response_status')
                ->orWhere('response_status', '>=', 400);
        });
    }

    /**
     * Scope to get successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if request failed
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }
}
