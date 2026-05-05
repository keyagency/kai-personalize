<?php

namespace KeyAgency\KaiPersonalize\Services;

use Illuminate\Http\Request;
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
        if (! config('kai-personalize.blacklist.enabled', false)) {
            return false;
        }

        $this->userAgent = $request->userAgent();
        $this->ip = $request->ip();

        // Check whitelist first (SEO bots moeten altijd door)
        if ($this->isWhitelistedBot()) {
            return false;
        }

        // Check blacklist
        if ($this->isBlacklisted()) {
            $this->logHit($request);
            return true;
        }

        return false;
    }

    protected function isWhitelistedBot(): bool
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

        $agentService = new AgentService($this->userAgent);
        $botName = strtolower($agentService->getBotName() ?? '');

        return in_array($botName, $whitelistedBots);
    }

    protected function isBlacklisted(): bool
    {
        $agentService = new AgentService($this->userAgent);
        $botName = $agentService->getBotName();

        // Check bot name blacklist
        if ($botName) {
            $match = Blacklist::active()
                ->byType('bot_name')
                ->where('pattern', strtolower($botName))
                ->first();

            if ($match) {
                $this->matchedBlacklist = $match;
                return true;
            }
        }

        // Check user agent pattern blacklist
        if ($this->userAgent) {
            $patterns = Blacklist::active()
                ->byType('user_agent')
                ->get();

            foreach ($patterns as $pattern) {
                if (str_contains(strtolower($this->userAgent), strtolower($pattern->pattern))) {
                    $this->matchedBlacklist = $pattern;
                    return true;
                }
            }
        }

        return false;
    }

    protected function logHit(Request $request): void
    {
        if (! config('kai-personalize.blacklist.logging', true)) {
            return;
        }

        $agentService = new AgentService($this->userAgent);
        $botName = $agentService->getBotName();

        BlacklistLog::create([
            'blacklist_id' => $this->matchedBlacklist->id ?? null,
            'bot_name' => $botName,
            'user_agent' => $this->userAgent,
            'ip_address' => $this->ip,
            'url' => $request->fullUrl(),
        ]);

        if (isset($this->matchedBlacklist)) {
            $this->matchedBlacklist->incrementHit();
        }
    }

    public function getMatchedBlacklist(): ?Blacklist
    {
        return $this->matchedBlacklist;
    }
}
