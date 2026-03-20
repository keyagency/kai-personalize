<?php

namespace KeyAgency\KaiPersonalize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for tracking endpoints
 *
 * Limits requests per IP address to prevent spam and data pollution
 */
class ThrottleTracking
{
    /**
     * Rate limit: requests per minute per IP
     */
    private const RATE_LIMIT = 120;

    /**
     * Rate limit: requests per hour per IP
     */
    private const HOURLY_LIMIT = 500;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $this->getClientIp($request);

        // Check minute rate limit
        $minuteKey = "kai:tracking:{$ip}:minute";
        $minuteCount = Cache::get($minuteKey, 0);

        if ($minuteCount >= self::RATE_LIMIT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        // Check hourly rate limit
        $hourlyKey = "kai:tracking:{$ip}:hourly";
        $hourlyCount = Cache::get($hourlyKey, 0);

        if ($hourlyCount >= self::HOURLY_LIMIT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }

        // Increment counters
        Cache::increment($minuteKey);
        Cache::remember($minuteKey, now()->addMinute(), fn () => 1);

        Cache::increment($hourlyKey);
        Cache::remember($hourlyKey, now()->addHour(), fn () => 1);

        return $next($request);
    }

    /**
     * Get client IP, respecting proxy headers
     */
    protected function getClientIp(Request $request): string
    {
        // Check for trusted proxy headers
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'];

        foreach ($headers as $header) {
            $ip = $request->server($header);

            if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                // X-Forwarded-For can contain multiple IPs, get the first one
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                return $ip;
            }
        }

        return $request->ip();
    }
}
