<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\Log;
use KeyAgency\KaiPersonalize\Models\PageView;
use KeyAgency\KaiPersonalize\Models\Rule;
use KeyAgency\KaiPersonalize\Models\Visitor;
use KeyAgency\KaiPersonalize\Services\AgentService;
use KeyAgency\KaiPersonalize\Services\Api\ApiManager;
use Statamic\Tags\Tags;

class Kai extends Tags
{
    protected static $handle = 'kai';

    // =========================================================================
    // VISITOR METHODS (from KaiVisitor)
    // =========================================================================

    /**
     * {{ kai:visitor }}
     */
    public function visitor(): array
    {
        $visitorId = Session::get(config('kai-personalize.session.visitor_id_key'));

        if (! $visitorId) {
            return $this->getAnonymousVisitorData();
        }

        $visitor = Visitor::findByFingerprint($visitorId);

        if (! $visitor) {
            return $this->getAnonymousVisitorData();
        }

        $currentSession = $visitor->sessions()->latest()->first();
        $userAgent = $currentSession?->user_agent;

        // Get agent attributes from stored data or parse live
        $agentData = $this->getAgentData($visitor, $userAgent);

        return array_merge([
            'fingerprint' => $visitor->fingerprint_hash,
            'session_id' => $currentSession?->session_id,
            'ip_address' => $currentSession?->ip_address,
            'user_agent' => $userAgent,
            'first_visit' => $visitor->first_visit_at,
            'last_visit' => $visitor->last_visit_at,
            'visit_count' => $visitor->visit_count,
            'is_returning' => $visitor->visit_count > 1,

            // Location (from geolocation)
            'country' => $visitor->getVisitorAttribute('country'),
            'city' => $visitor->getVisitorAttribute('city'),
            'region' => $visitor->getVisitorAttribute('region'),

            // User preferences
            'language' => $visitor->getVisitorAttribute('language'),
            'timezone' => $visitor->getVisitorAttribute('timezone'),

            // Traffic source
            'referrer' => $visitor->getVisitorAttribute('referrer'),
            'utm_source' => $visitor->getVisitorAttribute('utm_source'),
            'utm_medium' => $visitor->getVisitorAttribute('utm_medium'),
            'utm_campaign' => $visitor->getVisitorAttribute('utm_campaign'),
            'utm_term' => $visitor->getVisitorAttribute('utm_term'),
            'utm_content' => $visitor->getVisitorAttribute('utm_content'),
        ], $agentData);
    }

    protected function getAnonymousVisitorData(): array
    {
        $userAgent = request()->userAgent();
        $agentData = $this->parseAgentData($userAgent);

        return array_merge([
            'fingerprint' => null,
            'session_id' => Session::getId(),
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'first_visit' => now(),
            'last_visit' => now(),
            'visit_count' => 1,
            'is_returning' => false,

            // Location (unknown for anonymous)
            'country' => null,
            'city' => null,
            'region' => null,

            // User preferences
            'language' => app()->getLocale(),
            'timezone' => config('app.timezone'),

            // Traffic source (unknown for anonymous)
            'referrer' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
        ], $agentData);
    }

    protected function getAgentData(Visitor $visitor, ?string $userAgent): array
    {
        // Try to get stored browser attribute first
        $storedBrowser = $visitor->getVisitorAttribute('browser');

        // If we have stored data, use it
        if ($storedBrowser) {
            return [
                'browser' => $storedBrowser,
                'browser_version' => $visitor->getVisitorAttribute('browser_version'),
                'browser_version_major' => $visitor->getVisitorAttribute('browser_version_major'),
                'platform' => $visitor->getVisitorAttribute('platform'),
                'platform_version' => $visitor->getVisitorAttribute('platform_version'),
                'device' => $visitor->getVisitorAttribute('device'),
                'device_type' => $visitor->getVisitorAttribute('device_type') ?? 'desktop',
                'is_mobile' => $visitor->getVisitorAttribute('is_mobile') === '1',
                'is_tablet' => $visitor->getVisitorAttribute('is_tablet') === '1',
                'is_desktop' => $visitor->getVisitorAttribute('is_desktop') === '1',
                'is_phone' => $visitor->getVisitorAttribute('is_phone') === '1',
                'is_bot' => $visitor->getVisitorAttribute('is_bot') === '1',
                'bot_name' => $visitor->getVisitorAttribute('bot_name'),
                'accepted_languages' => $visitor->getVisitorAttribute('accepted_languages'),
            ];
        }

        // Otherwise, parse from user agent
        return $this->parseAgentData($userAgent);
    }

    protected function parseAgentData(?string $userAgent): array
    {
        if (! $userAgent) {
            return $this->getDefaultAgentData();
        }

        $agentService = new AgentService($userAgent);

        return [
            'browser' => $agentService->getBrowser(),
            'browser_version' => $agentService->getBrowserVersion(),
            'browser_version_major' => $agentService->getBrowserVersionMajor(),
            'platform' => $agentService->getPlatform(),
            'platform_version' => $agentService->getPlatformVersion(),
            'device' => $agentService->getDevice(),
            'device_type' => $agentService->getDeviceType(),
            'is_mobile' => $agentService->isMobile(),
            'is_tablet' => $agentService->isTablet(),
            'is_desktop' => $agentService->isDesktop(),
            'is_phone' => $agentService->isPhone(),
            'is_bot' => $agentService->isBot(),
            'bot_name' => $agentService->getBotName(),
            'accepted_languages' => implode(',', $agentService->getLanguages()),
        ];
    }

    protected function getDefaultAgentData(): array
    {
        return [
            'browser' => null,
            'browser_version' => null,
            'browser_version_major' => null,
            'platform' => null,
            'platform_version' => null,
            'device' => null,
            'device_type' => 'unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => false,
            'is_phone' => false,
            'is_bot' => false,
            'bot_name' => null,
            'accepted_languages' => null,
        ];
    }

    // =========================================================================
    // CONDITION METHODS (from KaiCondition)
    // =========================================================================

    /**
     * {{ kai:condition attribute="country" operator="equals" value="US" }}
     */
    public function condition(): bool
    {
        $attribute = $this->params->get('attribute');
        $operator = $this->params->get('operator', 'equals');
        $value = $this->params->get('value');

        if (! $attribute) {
            return false;
        }

        $visitorData = $this->getVisitorDataForCondition();
        $actualValue = data_get($visitorData, $attribute);

        return $this->evaluateCondition($actualValue, $operator, $value);
    }

    protected function getVisitorDataForCondition(): array
    {
        $visitorId = Session::get(config('kai-personalize.session.visitor_id_key'));

        if (! $visitorId) {
            return [];
        }

        $visitor = Visitor::findByFingerprint($visitorId);

        if (! $visitor) {
            return [];
        }

        $currentSession = $visitor->sessions()->latest()->first();
        $userAgent = $currentSession?->user_agent;
        $agentService = $userAgent ? new AgentService($userAgent) : null;

        return [
            'visit_count' => $visitor->visit_count,
            'is_returning' => $visitor->visit_count > 1,
            'country' => $visitor->getVisitorAttribute('country'),
            'city' => $visitor->getVisitorAttribute('city'),
            'region' => $visitor->getVisitorAttribute('region'),
            'browser' => $visitor->getVisitorAttribute('browser') ?? ($agentService?->getBrowser()),
            'device_type' => $visitor->getVisitorAttribute('device_type') ?? ($agentService?->getDeviceType() ?? 'desktop'),
            'platform' => $visitor->getVisitorAttribute('platform') ?? ($agentService?->getPlatform()),
            'is_mobile' => ($visitor->getVisitorAttribute('is_mobile') === '1') || ($agentService?->isMobile() ?? false),
            'is_tablet' => ($visitor->getVisitorAttribute('is_tablet') === '1') || ($agentService?->isTablet() ?? false),
            'is_desktop' => ($visitor->getVisitorAttribute('is_desktop') === '1') || ($agentService?->isDesktop() ?? false),
            'is_bot' => ($visitor->getVisitorAttribute('is_bot') === '1') || ($agentService?->isBot() ?? false),
            'language' => $visitor->getVisitorAttribute('language'),
            'timezone' => $visitor->getVisitorAttribute('timezone'),
            'time_of_day' => now()->format('H'),
            'day_of_week' => now()->dayOfWeek,
            'utm_source' => $visitor->getVisitorAttribute('utm_source'),
            'utm_medium' => $visitor->getVisitorAttribute('utm_medium'),
            'utm_campaign' => $visitor->getVisitorAttribute('utm_campaign'),
            'utm_term' => $visitor->getVisitorAttribute('utm_term'),
            'utm_content' => $visitor->getVisitorAttribute('utm_content'),
        ];
    }

    protected function evaluateCondition($actualValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            'equals', '==' => $actualValue == $expectedValue,
            'not_equals', '!=' => $actualValue != $expectedValue,
            'contains' => str_contains((string) $actualValue, (string) $expectedValue),
            'not_contains' => ! str_contains((string) $actualValue, (string) $expectedValue),
            'greater_than', '>' => $actualValue > $expectedValue,
            'less_than', '<' => $actualValue < $expectedValue,
            'greater_or_equal', '>=' => $actualValue >= $expectedValue,
            'less_or_equal', '<=' => $actualValue <= $expectedValue,
            'in' => in_array($actualValue, (array) $expectedValue),
            'not_in' => ! in_array($actualValue, (array) $expectedValue),
            'starts_with' => str_starts_with((string) $actualValue, (string) $expectedValue),
            'ends_with' => str_ends_with((string) $actualValue, (string) $expectedValue),
            default => false,
        };
    }

    // =========================================================================
    // CONTENT METHODS (from KaiContent)
    // =========================================================================

    /**
     * {{ kai:content rules="rule-id-or-slug" }}
     */
    public function content(): array
    {
        $rulesParam = $this->params->get('rules');
        $fallback = $this->params->get('fallback');

        if (! $rulesParam) {
            return ['condition_met' => false];
        }

        // Get visitor data
        $visitorData = $this->getVisitorDataForCondition();
        $visitor = $this->getCurrentVisitor();

        // Get rules
        $rules = is_array($rulesParam) ? $rulesParam : [$rulesParam];

        foreach ($rules as $ruleIdentifier) {
            $rule = is_numeric($ruleIdentifier)
                ? Rule::find($ruleIdentifier)
                : Rule::where('name', $ruleIdentifier)->first();

            if (! $rule || ! $rule->is_active) {
                continue;
            }

            // Evaluate rule
            if ($rule->evaluate($visitorData)) {
                // Log the match
                if ($visitor) {
                    Log::createEntry(
                        $visitor->id,
                        $rule->id,
                        $rule->conditions,
                        ['rule' => $rule->name]
                    );
                }

                return [
                    'condition_met' => true,
                    'rule_name' => $rule->name,
                    'rule_id' => $rule->id,
                ];
            }
        }

        return [
            'condition_met' => false,
            'fallback' => $fallback,
        ];
    }

    protected function getCurrentVisitor(): ?Visitor
    {
        $visitorId = Session::get(config('kai-personalize.session.visitor_id_key'));

        if (! $visitorId) {
            return null;
        }

        return Visitor::findByFingerprint($visitorId);
    }

    // =========================================================================
    // EXTERNAL API METHODS (from KaiExternal)
    // =========================================================================

    /**
     * {{ kai:external source="weather" }}
     */
    public function external(): array
    {
        $source = $this->params->get('source');

        if (! $source) {
            return [];
        }

        try {
            $apiManager = new ApiManager;

            $data = match ($source) {
                'weather' => $this->fetchWeather($apiManager),
                'geolocation' => $this->fetchGeolocation($apiManager),
                'news' => [],
                'exchange' => [],
                'custom' => $this->fetchCustom($apiManager),
                default => [],
            };

            return $data;
        } catch (\Exception $e) {
            \Log::error('KaiExternal tag error', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function fetchWeather(ApiManager $apiManager): array
    {
        $location = $this->params->get('location', 'auto');
        $units = $this->params->get('units', 'metric');

        return $apiManager->weather([
            'location' => $location,
            'units' => $units,
        ]);
    }

    protected function fetchGeolocation(ApiManager $apiManager): array
    {
        $ip = $this->params->get('ip', request()->ip());

        return $apiManager->geolocation($ip);
    }

    protected function fetchCustom(ApiManager $apiManager): array
    {
        $connection = $this->params->get('connection');
        $endpoint = $this->params->get('endpoint', '/');

        if (! $connection) {
            return [];
        }

        $params = [];
        foreach ($this->params->all() as $key => $value) {
            if (str_starts_with($key, 'params:')) {
                $paramKey = substr($key, 7);
                $params[$paramKey] = $value;
            }
        }

        $params['endpoint'] = $endpoint;

        return $apiManager->custom($connection, $params);
    }

    // =========================================================================
    // SESSION METHODS (from KaiSession)
    // =========================================================================

    /**
     * {{ kai:session:set key="value" }}
     */
    public function sessionSet(): string
    {
        $key = $this->params->get('key');
        $value = $this->params->get('value');

        if ($key) {
            Session::put('kai_'.$key, $value);
        }

        return '';
    }

    /**
     * {{ kai:session:get key="key" }}
     */
    public function sessionGet(): mixed
    {
        $key = $this->params->get('key');

        if (! $key) {
            return null;
        }

        return Session::get('kai_'.$key);
    }

    /**
     * {{ kai:session:tracked }}
     */
    public function sessionTracked(): bool
    {
        return Session::has(config('kai-personalize.session.visitor_id_key'));
    }

    /**
     * {{ kai:session:forget key="key" }}
     */
    public function sessionForget(): string
    {
        $key = $this->params->get('key');

        if ($key) {
            Session::forget('kai_'.$key);
        }

        return '';
    }

    // =========================================================================
    // API METHODS (from KaiApi)
    // =========================================================================

    /**
     * {{ kai:api url="https://api.example.com/data" method="GET" cache="600" }}
     */
    public function api(): array
    {
        $url = $this->params->get('url');
        $method = $this->params->get('method', 'GET');
        $cacheDuration = $this->params->get('cache', 300);

        if (! $url) {
            return [];
        }

        $params = [];
        foreach ($this->params->all() as $key => $value) {
            if (str_starts_with($key, 'params:')) {
                $paramKey = substr($key, 7);
                $params[$paramKey] = $value;
            }
        }

        $cacheKey = 'kai_api_'.md5($url.json_encode($params));

        if ($cacheDuration > 0 && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = match (strtoupper($method)) {
                'GET' => Http::timeout(30)->get($url, $params),
                'POST' => Http::timeout(30)->post($url, $params),
                'PUT' => Http::timeout(30)->put($url, $params),
                'DELETE' => Http::timeout(30)->delete($url, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $data = $response->json() ?? [];

            if ($cacheDuration > 0) {
                Cache::put($cacheKey, $data, $cacheDuration);
            }

            return $data;

        } catch (\Exception $e) {
            \Log::error('KaiApi tag error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    // =========================================================================
    // PAGE VIEW METHODS
    // =========================================================================

    /**
     * {{ kai:page_views collection="articles" limit="5" }}
     *   <a href="{{ url }}">{{ title }}</a> — {{ viewed_human }}
     * {{ /kai:page_views }}
     */
    public function pageViews(): array
    {
        $visitor = $this->getCurrentVisitor();

        if (! $visitor) {
            return [];
        }

        $collection = $this->params->get('collection');
        $limit = $this->params->get('limit', 10);

        $query = $visitor->pageViews()->orderByDesc('viewed_at');

        if ($collection) {
            $query->forCollection($collection);
        }

        return $query->limit($limit)->get()->map(function (PageView $pageView) {
            return [
                'url' => $pageView->url_path,
                'title' => $pageView->entry_title,
                'slug' => $pageView->entry_slug,
                'collection' => $pageView->collection_handle,
                'viewed_at' => $pageView->viewed_at,
                'viewed_human' => $pageView->viewed_at?->diffForHumans(),
                'is_entry' => $pageView->entry_slug !== null,
            ];
        })->toArray();
    }

    // =========================================================================
    // SEGMENT METHODS (from KaiSegment)
    // =========================================================================

    /**
     * {{ kai:segment name="returning-visitors" }}
     */
    public function segment(): bool
    {
        $segmentName = $this->params->get('name');

        if (! $segmentName) {
            return false;
        }

        // TODO: Implement segment evaluation
        return false;
    }

    // =========================================================================
    // TRACKING METHODS (from KaiTrack)
    // =========================================================================

    /**
     * {{ kai:track }}
     * Outputs inline JavaScript for client-side behavioral tracking
     */
    public function track(): string
    {
        $trackTag = new \KeyAgency\KaiPersonalize\Tags\KaiTrack;
        $trackTag->setContext($this->context);
        $trackTag->setParameters($this->params);

        return $trackTag->track();
    }

    // =========================================================================
    // TRACKING SIGNATURE METHODS
    // =========================================================================

    /**
     * {{ kai:tracking }}
     * Returns signature data for secure client-side tracking
     *
     * Usage: {{ kai:tracking }}
     *   Returns: signature, nonce, timestamp, enabled, visitor_id
     */
    public function tracking(): array
    {
        $trackingTag = new \KeyAgency\KaiPersonalize\Tags\KaiTracking;
        $trackingTag->setContext($this->context);
        $trackingTag->setParameters($this->params);

        return $trackingTag->index();
    }

    /**
     * {{ kai:tracking:signature }}
     * Alias for tracking method
     */
    public function trackingSignature(): array
    {
        return $this->tracking();
    }

    // =========================================================================
    // BEHAVIOR METHODS (from KaiBehavior)
    // =========================================================================

    /**
     * {{ kai:behavior }}
     * Returns current visitor's behavioral data
     */
    public function behavior(): array
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->behavior();
    }

    /**
     * {{ kai:if:scroll_depth operator=">=" value="75" }}
     */
    public function ifScrollDepth(): bool
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->ifScrollDepth();
    }

    /**
     * {{ kai:if:active_time operator=">" value="30" }}
     */
    public function ifActiveTime(): bool
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->ifActiveTime();
    }

    /**
     * {{ kai:if:page_views operator=">=" value="3" }}
     */
    public function ifPageViews(): bool
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->ifPageViews();
    }

    /**
     * {{ kai:if:visited url="/about" }}
     */
    public function ifVisited(): bool
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->ifVisited();
    }

    /**
     * {{ kai:events type="scroll_depth" limit="10" }}
     */
    public function events(): array
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->events();
    }

    /**
     * {{ kai:sections_viewed }}
     */
    public function sectionsViewed(): array
    {
        $behaviorTag = new \KeyAgency\KaiPersonalize\Tags\KaiBehavior;
        $behaviorTag->setContext($this->context);
        $behaviorTag->setParameters($this->params);

        return $behaviorTag->sectionsViewed();
    }
}
