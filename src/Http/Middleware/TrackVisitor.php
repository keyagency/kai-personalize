<?php

namespace KeyAgency\KaiPersonalize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\PageView;
use KeyAgency\KaiPersonalize\Models\Visitor;
use KeyAgency\KaiPersonalize\Models\VisitorSession;
use KeyAgency\KaiPersonalize\Services\ActiveCampaignService;
use KeyAgency\KaiPersonalize\Services\AgentService;
use KeyAgency\KaiPersonalize\Services\MaxMindService;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class TrackVisitor
{
    public function handle(Request $request, Closure $next)
    {
        // Check master kill switch first
        if (! config('kai-personalize.enabled', true)) {
            return $next($request);
        }

        // Skip tracking for these routes to prevent interference with authentication and admin
        if ($this->shouldSkipTracking($request)) {
            return $next($request);
        }

        // Only track collection entries (pages, cases, articles, etc.)
        if (! $this->isCollectionEntry($request)) {
            return $next($request);
        }

        try {
            // Skip tracking if fingerprinting is disabled
            if (! config('kai-personalize.features.fingerprinting', false)) {
                return $next($request);
            }

            // Respect Do Not Track header
            if (config('kai-personalize.privacy.respect_dnt', true) && $request->header('DNT') === '1') {
                return $next($request);
            }

            // Check if cookie consent is required and not given
            if (config('kai-personalize.privacy.cookie_consent_required', false) && ! $this->hasConsent($request)) {
                return $next($request);
            }

            // Get or create session ID
            $sessionId = Session::getId();

            // Try to get fingerprint from session
            $fingerprintHash = Session::get(config('kai-personalize.session.visitor_id_key', 'kai_visitor_id'));

            // If no fingerprint in session and fingerprint fallback is enabled
            if (! $fingerprintHash && config('kai-personalize.session.use_fingerprint_fallback', true)) {
                // The fingerprint will be sent via JavaScript after page load
                // For now, use session ID as temporary identifier
                $fingerprintHash = 'temp_'.$sessionId;
            }

            if ($fingerprintHash) {
                // Create or update visitor
                $visitor = Visitor::createOrUpdate($fingerprintHash, $sessionId);

                // Create or update session
                $ipAddress = config('kai-personalize.features.ip_tracking', true) ? $request->ip() : null;
                $userAgent = $request->userAgent();

                $visitorSession = VisitorSession::createOrUpdate(
                    $visitor->id,
                    $sessionId,
                    $ipAddress,
                    $userAgent
                );

                // Store visitor ID in session for easy access
                Session::put(config('kai-personalize.session.visitor_id_key', 'kai_visitor_id'), $fingerprintHash);
                Session::put(config('kai-personalize.session.session_id_key', 'kai_session_id'), $visitorSession->id);

                // Collect additional attributes if behavioral tracking is enabled
                if (config('kai-personalize.features.behavioral_tracking', true)) {
                    $this->collectAttributes($request, $visitor, $visitorSession);
                }

                // Track individual page view if feature is enabled
                if (config('kai-personalize.features.page_view_tracking', true)) {
                    $this->trackPageView($request, $visitor, $visitorSession);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break the request
            Log::error('Kai Personalize tracking error: '.$e->getMessage(), [
                'exception' => $e,
                'url' => $request->fullUrl(),
            ]);
        }

        return $next($request);
    }

    /**
     * Determine if tracking should be skipped for this request
     */
    protected function shouldSkipTracking(Request $request): bool
    {
        $path = $request->path();

        // Skip CP routes, API routes, and tracking endpoints
        $skipPatterns = [
            'cp',
            'cp/*',
            'api/*',
            'kai-personalize/*',
            'livewire/*',
            '_ignition/*',
            'password/*',
            'auth/*',
            'oauth/*',
        ];

        foreach ($skipPatterns as $pattern) {
            if ($this->pathMatches($path, $pattern)) {
                return true;
            }
        }

        // Skip auth-related routes
        if (str_contains($path, 'login') ||
            str_contains($path, 'logout') ||
            str_contains($path, 'register')) {
            return true;
        }

        // Skip AJAX and JSON requests
        return $request->ajax() || $request->expectsJson();
    }

    /**
     * Check if a path matches a pattern
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Exact match
        if ($path === $pattern) {
            return true;
        }

        // Wildcard match
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');
            return str_starts_with($path, $prefix);
        }

        return false;
    }

    /**
     * Check if the current URL is a collection entry
     */
    protected function isCollectionEntry(Request $request): bool
    {
        $urlPath = '/'.ltrim($request->path(), '/');

        return $this->findEntryByUri($urlPath) !== null;
    }

    /**
     * Check if user has given consent for tracking
     */
    protected function hasConsent(Request $request): bool
    {
        // Check for common cookie consent cookies
        return $request->cookie('cookie_consent') === 'accepted'
            || $request->cookie('gdpr_consent') === 'true'
            || Session::get('kai_consent') === true;
    }

    /**
     * Collect visitor attributes
     */
    protected function collectAttributes(Request $request, Visitor $visitor, VisitorSession $visitorSession): void
    {
        $sessionId = $visitorSession->id;

        // Collect referrer (session-scoped)
        if ($referrer = $request->header('referer')) {
            $visitor->setVisitorAttribute('referrer', $referrer, 'personal', null, $sessionId);
        }

        // Collect UTM parameters (session-scoped)
        $utmParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utmParams as $param) {
            if ($request->has($param)) {
                $visitor->setVisitorAttribute($param, $request->get($param), 'personal', null, $sessionId);
            }
        }

        // Collect language
        $locale = app()->getLocale();
        $visitor->setVisitorAttribute('language', $locale, 'personal');

        // Collect time-based attributes
        $visitor->setVisitorAttribute('time_of_day', now()->format('H'), 'personal');
        $visitor->setVisitorAttribute('day_of_week', now()->dayOfWeek, 'personal');

        // Collect browser/agent attributes
        $this->collectAgentAttributes($request, $visitor);

        // Collect geolocation attributes (if enabled and IP tracking is on)
        if (config('kai-personalize.features.geolocation', true) && config('kai-personalize.features.ip_tracking', true)) {
            $this->collectGeolocationAttributes($request, $visitor);
        }

        // Collect ActiveCampaign attributes (if enabled)
        if (config('kai-personalize.features.activecampaign', false)) {
            $this->collectActiveCampaignAttributes($visitor);
        }
    }

    /**
     * Collect browser and device attributes using AgentService
     * Only sets attributes that don't already exist for this visitor
     */
    protected function collectAgentAttributes(Request $request, Visitor $visitor): void
    {
        $userAgent = $request->userAgent();

        if (! $userAgent) {
            return;
        }

        $agentService = new AgentService($userAgent);

        // Browser attributes - only set if not already present
        $this->setAttributeIfMissing($visitor, 'browser', $agentService->getBrowser(), 'technical');
        $this->setAttributeIfMissing($visitor, 'browser_version', $agentService->getBrowserVersion(), 'technical');
        $this->setAttributeIfMissing($visitor, 'browser_version_major', $agentService->getBrowserVersionMajor() ? (string) $agentService->getBrowserVersionMajor() : null, 'technical');

        // Platform/OS attributes
        $this->setAttributeIfMissing($visitor, 'platform', $agentService->getPlatform(), 'technical');
        $this->setAttributeIfMissing($visitor, 'platform_version', $agentService->getPlatformVersion(), 'technical');

        // Device attributes
        $this->setAttributeIfMissing($visitor, 'device', $agentService->getDevice(), 'technical');
        $this->setAttributeIfMissing($visitor, 'device_type', $agentService->getDeviceType(), 'technical');

        // Device capability flags
        $this->setAttributeIfMissing($visitor, 'is_mobile', $agentService->isMobile() ? '1' : '0', 'technical');
        $this->setAttributeIfMissing($visitor, 'is_tablet', $agentService->isTablet() ? '1' : '0', 'technical');
        $this->setAttributeIfMissing($visitor, 'is_desktop', $agentService->isDesktop() ? '1' : '0', 'technical');
        $this->setAttributeIfMissing($visitor, 'is_phone', $agentService->isPhone() ? '1' : '0', 'technical');

        // Bot detection
        $this->setAttributeIfMissing($visitor, 'is_bot', $agentService->isBot() ? '1' : '0', 'technical');
        $this->setAttributeIfMissing($visitor, 'bot_name', $agentService->getBotName(), 'technical');

        // Accepted languages from headers
        $languages = $agentService->getLanguages();
        if (! empty($languages)) {
            $this->setAttributeIfMissing($visitor, 'accepted_languages', implode(',', $languages), 'technical');
        }
    }

    /**
     * Collect geolocation attributes using MaxMind local database
     */
    protected function collectGeolocationAttributes(Request $request, Visitor $visitor): void
    {
        // Check if MaxMind is enabled
        if (! config('kai-personalize.maxmind.enabled', true)) {
            return;
        }

        $ip = $request->ip();

        if (! $ip) {
            return;
        }

        try {
            $maxmind = new MaxMindService;

            if (! $maxmind->isAvailable()) {
                return;
            }

            $location = $maxmind->lookup($ip);

            if (! $location) {
                return;
            }

            // Store geolocation attributes (only if missing)
            $this->setAttributeIfMissing($visitor, 'country', $location['country'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'country_code', $location['country_code'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'region', $location['region'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'region_code', $location['region_code'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'city', $location['city'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'postal_code', $location['postal_code'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'continent', $location['continent'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'continent_code', $location['continent_code'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'timezone', $location['timezone'] ?? null, 'external');
            $this->setAttributeIfMissing($visitor, 'is_eu', ($location['is_eu'] ?? false) ? '1' : '0', 'external');

            // Store coordinates if available (and privacy allows)
            if (! config('kai-personalize.privacy.gdpr_mode', false)) {
                if (isset($location['latitude']) && isset($location['longitude'])) {
                    $this->setAttributeIfMissing($visitor, 'latitude', (string) $location['latitude'], 'external');
                    $this->setAttributeIfMissing($visitor, 'longitude', (string) $location['longitude'], 'external');
                }
            }

            // Store ISP info if available
            $this->setAttributeIfMissing($visitor, 'isp', $location['isp'] ?? null, 'external');

        } catch (\Exception $e) {
            Log::warning('MaxMind geolocation error: '.$e->getMessage());
        }
    }

    /**
     * Collect ActiveCampaign contact attributes from tracking cookie
     */
    protected function collectActiveCampaignAttributes(Visitor $visitor): void
    {
        try {
            $acService = app(ActiveCampaignService::class);

            if (! $acService->isEnabled()) {
                return;
            }

            $contact = $acService->getContactFromCookie();

            if (! $contact) {
                return;
            }

            // Store as 'crm' type attributes
            foreach ($contact as $key => $value) {
                if ($value !== null) {
                    // Convert arrays to JSON for storage
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $visitor->setVisitorAttribute($key, $value, 'crm');
                }
            }

        } catch (\Exception $e) {
            Log::warning('ActiveCampaign attribute collection error: '.$e->getMessage());
        }
    }

    /**
     * Track individual page view with Statamic entry metadata
     */
    protected function trackPageView(Request $request, Visitor $visitor, VisitorSession $visitorSession): void
    {
        $urlPath = '/'.ltrim($request->path(), '/');

        $entrySlug = null;
        $entryTitle = null;
        $collectionHandle = null;

        try {
            $entry = $this->findEntryByUri($urlPath);

            if ($entry && is_object($entry)) {
                $entrySlug = method_exists($entry, 'slug') ? $entry->slug() : null;
                $entryTitle = method_exists($entry, 'get') ? $entry->get('title') : null;
                $entryTitle = $entryTitle ?? (method_exists($entry, 'title') ? $entry->title() : null);
                $collectionHandle = $this->getCollectionHandle($entry);
            }
        } catch (\Exception $e) {
            Log::error('Kai PageView tracking error', [
                'url' => $urlPath,
                'error' => $e->getMessage(),
            ]);
        }

        PageView::createView(
            $visitor->id,
            $visitorSession->id,
            $urlPath,
            $entrySlug,
            $entryTitle,
            $collectionHandle
        );
    }

    /**
     * Get collection handle from an entry/page object
     * Handles both Entry and Page (structured content) types
     */
    protected function getCollectionHandle($entry): ?string
    {
        // For structured pages (Statamic\Structures\Page), get collection from root or structure
        if ($entry instanceof \Statamic\Structures\Page) {
            // Method 1: Try structure() method directly
            if (method_exists($entry, 'structure')) {
                $structure = $entry->structure();
                if ($structure) {
                    return $structure->handle();
                }
            }

            // Method 2: Try getting collection from root page
            if (method_exists($entry, 'root')) {
                $root = $entry->root();
                if ($root && method_exists($root, 'collection')) {
                    $collection = $root->collection();
                    if ($collection) {
                        return $collection->handle();
                    }
                }
            }

            return null;
        }

        // For regular entries (Statamic\Entries\Entry) and others
        // Method 1: Use collectionHandle() method if available
        if (method_exists($entry, 'collectionHandle')) {
            return $entry->collectionHandle();
        }

        // Method 2: Use collection()->handle()
        if (method_exists($entry, 'collection')) {
            $collection = $entry->collection();
            if ($collection) {
                return $collection->handle();
            }
        }

        return null;
    }

    /**
     * Find an entry by URI using multiple fallback methods
     */
    protected function findEntryByUri(string $urlPath)
    {
        $entry = null;
        // Ensure URI has leading slash for matching (Statamic stores URIs with /)
        $uriWithSlash = '/'.ltrim($urlPath, '/');

        $currentSite = Site::current();
        $siteHandle = is_object($currentSite) ? $currentSite->handle() : $currentSite['handle'] ?? Site::default()->handle();

        // Special handling for homepage (root URL)
        // The homepage in the pages collection has an empty URI
        if ($uriWithSlash === '/') {
            $entry = $this->findHomepageEntry($siteHandle);
            if ($entry) {
                return $entry;
            }
        }

        // Method 1: Try Statamic's Entry::findByUri (without leading slash)
        $entry = Entry::findByUri(ltrim($urlPath, '/'), $siteHandle);

        // Method 2: Try with full URL path
        if (! $entry) {
            $entry = Entry::findByUri($urlPath, $siteHandle);
        }

        // Method 3: Try without site handle
        if (! $entry) {
            $entry = Entry::findByUri(ltrim($urlPath, '/'));
        }

        // Method 4: For multi-site, try with locale prefix
        if (! $entry && Site::hasMultiple()) {
            $locale = $currentSite->locale();

            // Try with locale prefix
            $entry = Entry::findByUri($locale.'/'.ltrim($urlPath, '/'), $siteHandle);

            // Try with site handle prefix
            if (! $entry) {
                $entry = Entry::findByUri($siteHandle.'/'.ltrim($urlPath, '/'), $siteHandle);
            }
        }

        // Method 5: Fallback - iterate through collections and find by URI
        if (! $entry) {
            $entry = $this->findEntryByCheckingCollections($uriWithSlash, $siteHandle);
        }

        return $entry;
    }

    /**
     * Find entry by checking all collections and matching URIs
     * This handles structured collections where the route pattern is complex
     */
    protected function findEntryByCheckingCollections(string $uri, string $siteHandle)
    {
        // Get all collections
        $collections = \Statamic\Facades\Collection::all();

        foreach ($collections as $collection) {
            // Query entries in this collection for the current site
            $entry = $collection->queryEntries()
                ->where('site', $siteHandle)
                ->where('uri', $uri)
                ->first();

            if ($entry) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Find the homepage entry from the pages collection
     */
    protected function findHomepageEntry(string $siteHandle)
    {
        $collection = \Statamic\Facades\Collection::findByHandle('pages');
        if (! $collection) {
            return null;
        }

        // Method 1: Homepage entries have empty URI in the pages collection
        $entry = $collection->queryEntries()
            ->where('site', $siteHandle)
            ->where('uri', '')
            ->first();

        if ($entry && is_object($entry)) {
            return $entry;
        }

        // Method 2: Try finding by structure's root entry (if collection has structure)
        if ($collection->hasStructure()) {
            $structure = $collection->structure();
            if ($structure) {
                $tree = $structure->in($siteHandle);
                if ($tree) {
                    $root = $tree->root();
                    // Only return if it's an actual Page/Entry object
                    if ($root && is_object($root) && !is_array($root)) {
                        return $root;
                    }
                }
            }
        }

        // Method 3: Try finding entry with slug 'home'
        $entry = $collection->queryEntries()
            ->where('site', $siteHandle)
            ->where('slug', 'home')
            ->first();

        return ($entry && is_object($entry)) ? $entry : null;
    }

    /**
     * Set a visitor attribute only if it doesn't already exist
     */
    protected function setAttributeIfMissing(Visitor $visitor, string $key, ?string $value, string $type = 'personal'): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Check if attribute already exists
        $existing = $visitor->getVisitorAttribute($key);

        if ($existing === null) {
            $visitor->setVisitorAttribute($key, $value, $type);
        }
    }
}
