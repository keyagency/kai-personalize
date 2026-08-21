<?php

namespace KeyAgency\KaiPersonalize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for tracking endpoints
 *
 * Limits requests per IP address to prevent spam and data pollution. Both windows are
 * configurable through kai-personalize.tracking.rate_limit; a limit of 0 disables one.
 */
class ThrottleTracking
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $request->ip() honours the application's trusted proxies. Earlier versions read
        // X-Forwarded-For and friends straight off the request, which let any client pick
        // its own bucket and disagreed with the IP the tracking itself records. Behind a
        // proxy, configure trustProxies (see the README).
        $windows = $this->windows($request->ip());

        foreach ($windows as $window) {
            if (RateLimiter::tooManyAttempts($window['key'], $window['limit'])) {
                return $this->tooManyRequests($window);
            }
        }

        foreach ($windows as $window) {
            RateLimiter::hit($window['key'], $window['decay']);
        }

        return $next($request);
    }

    /**
     * The active rate limit windows for an IP, leaving out the ones that are disabled.
     *
     * The cache keys deliberately differ from the ones used before 1.2.12: those counters
     * were written without a TTL on a file cache store and never expired, so reusing the
     * names would inherit a poisoned counter that keeps answering 429.
     */
    protected function windows(string $ip): array
    {
        return array_values(array_filter([
            [
                'key' => "kai-personalize:track:{$ip}:minute",
                'limit' => $this->limit('per_minute', 120),
                'decay' => 60,
                'message' => 'Too many requests. Please try again later.',
            ],
            [
                'key' => "kai-personalize:track:{$ip}:hour",
                'limit' => $this->limit('per_hour', 1000),
                'decay' => 3600,
                'message' => 'Rate limit exceeded. Please try again later.',
            ],
        ], fn (array $window) => $window['limit'] > 0));
    }

    /**
     * Read a configured limit, falling back to the default when a published config
     * predates the setting.
     */
    protected function limit(string $key, int $default): int
    {
        return (int) config("kai-personalize.tracking.rate_limit.{$key}", $default);
    }

    /**
     * Refuse the request, telling the client when it may come back.
     */
    protected function tooManyRequests(array $window): Response
    {
        return response()->json([
            'status' => 'error',
            'message' => $window['message'],
        ], 429, [
            'Retry-After' => RateLimiter::availableIn($window['key']),
            'X-RateLimit-Limit' => $window['limit'],
            'X-RateLimit-Remaining' => 0,
        ]);
    }
}
