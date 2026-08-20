<?php

namespace KeyAgency\KaiPersonalize\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use KeyAgency\KaiPersonalize\Models\Blacklist;
use KeyAgency\KaiPersonalize\Models\BlacklistLog;

class BlacklistService
{
    protected ?string $userAgent = null;
    protected ?string $ip = null;
    protected ?Blacklist $matchedBlacklist = null;

    public function __construct(?string $userAgent = null, ?string $ip = null)
    {
        $this->userAgent = $userAgent;
        $this->ip = $ip;
    }

    public function shouldBlock(Request $request): bool
    {
        if (! config('kai-personalize.blacklist.enabled', true)) {
            return false;
        }

        $this->userAgent = $request->userAgent();
        $this->ip = $request->ip();
        $this->matchedBlacklist = null;

        $agent = new AgentService($this->userAgent);

        // Check whitelist first (SEO bots moeten altijd door)
        if ($this->isWhitelistedBot($agent)) {
            return false;
        }

        // Check blacklist
        if ($this->isBlacklisted($agent)) {
            $this->logHit($request);

            return true;
        }

        // Catch-all for bots the parser recognises but nobody listed. Not logged:
        // one row per bot request would just move the growth to another table.
        return config('kai-personalize.blacklist.skip_known_bots', true) && $agent->isBot();
    }

    protected function isWhitelistedBot(?AgentService $agent = null): bool
    {
        $whitelistedBots = [
            'googlebot',
            'bingbot',
            'slurp',
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
        ];

        $agent ??= new AgentService($this->userAgent);
        $botName = strtolower($agent->getBotName() ?? '');

        return in_array($botName, $whitelistedBots);
    }

    protected function isBlacklisted(?AgentService $agent = null): bool
    {
        $agent ??= new AgentService($this->userAgent);
        $botName = $agent->getBotName();

        // Check bot name blacklist
        if ($botName) {
            $match = $this->activePatterns('bot_name')
                ->firstWhere('pattern', strtolower($botName));

            if ($match) {
                $this->matchedBlacklist = $match;

                return true;
            }
        }

        // Check user agent pattern blacklist
        if ($this->userAgent) {
            $userAgent = strtolower($this->userAgent);

            foreach ($this->activePatterns('user_agent') as $pattern) {
                if (str_contains($userAgent, strtolower($pattern->pattern))) {
                    $this->matchedBlacklist = $pattern;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Active patterns of a type, cached so every request does not query them.
     */
    protected function activePatterns(string $type): Collection
    {
        $cached = Cache::remember(
            'kai:blacklist:patterns:'.$type,
            config('kai-personalize.blacklist.pattern_cache_ttl', 600),
            fn () => Blacklist::active()->byType($type)->get()->all()
        );

        return collect($cached);
    }

    protected function logHit(Request $request): void
    {
        // Counted before the log row, and outside the logging toggle: a hit belongs
        // to the match itself, not to recording it. In the old order a failed insert
        // took the count down with it, and with logging off it disappeared entirely.
        if (isset($this->matchedBlacklist)) {
            $this->matchedBlacklist->incrementHit();
        }

        if (! config('kai-personalize.blacklist.logging', true)) {
            return;
        }

        $agentService = new AgentService($this->userAgent);
        $botName = $agentService->getBotName();

        BlacklistLog::create([
            'blacklist_id' => $this->matchedBlacklist->id ?? null,
            'bot_name' => $botName,
            // url and user_agent are varchar(255), and a blocked bot is exactly the
            // visitor that arrives with an outsized query string or user agent.
            // Unclipped, the insert fails with SQLSTATE[22001]; that exception
            // bubbles up to TrackVisitor and surfaces as "Kai Personalize tracking
            // error" even though tracking itself is fine. The net effect was a
            // blacklist that blocked traffic but recorded not a single hit.
            'user_agent' => $this->truncate($this->userAgent),
            'ip_address' => $this->ip,
            'url' => $this->truncate($request->fullUrl()),
        ]);
    }

    /**
     * Clip a value to what a varchar(255) column will hold.
     */
    protected function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > 255 ? mb_substr($value, 0, 255) : $value;
    }

    public function getMatchedBlacklist(): ?Blacklist
    {
        return $this->matchedBlacklist;
    }
}
