<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\Visitor;
use KeyAgency\KaiPersonalize\Services\AgentService;
use Statamic\Tags\Tags;

class KaiVisitor extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

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

            // ActiveCampaign data (if available)
            'ac_contact_id' => $this->parseJsonAttribute($visitor->getVisitorAttribute('ac_contact_id')),
            'ac_email' => $visitor->getVisitorAttribute('ac_email'),
            'ac_first_name' => $visitor->getVisitorAttribute('ac_first_name'),
            'ac_last_name' => $visitor->getVisitorAttribute('ac_last_name'),
            'ac_phone' => $visitor->getVisitorAttribute('ac_phone'),
            'ac_tags' => $this->parseJsonAttribute($visitor->getVisitorAttribute('ac_tags')) ?? [],
            'ac_lists' => $this->parseJsonAttribute($visitor->getVisitorAttribute('ac_lists')) ?? [],
            'ac_custom_fields' => $this->parseJsonAttribute($visitor->getVisitorAttribute('ac_custom_fields')) ?? [],
            'ac_created_at' => $visitor->getVisitorAttribute('ac_created_at'),
            'ac_updated_at' => $visitor->getVisitorAttribute('ac_updated_at'),
        ], $agentData);
    }

    /**
     * Get anonymous visitor data
     */
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

            // ActiveCampaign data (not available for anonymous)
            'ac_contact_id' => null,
            'ac_email' => null,
            'ac_first_name' => null,
            'ac_last_name' => null,
            'ac_phone' => null,
            'ac_tags' => [],
            'ac_lists' => [],
            'ac_custom_fields' => [],
            'ac_created_at' => null,
            'ac_updated_at' => null,
        ], $agentData);
    }

    /**
     * Get agent data from stored attributes or parse live
     */
    protected function getAgentData(Visitor $visitor, ?string $userAgent): array
    {
        // Try to get stored browser attribute first
        $storedBrowser = $visitor->getVisitorAttribute('browser');

        // If we have stored data, use it
        if ($storedBrowser) {
            return [
                // Browser info
                'browser' => $storedBrowser,
                'browser_version' => $visitor->getVisitorAttribute('browser_version'),
                'browser_version_major' => $visitor->getVisitorAttribute('browser_version_major'),

                // Platform/OS info
                'platform' => $visitor->getVisitorAttribute('platform'),
                'platform_version' => $visitor->getVisitorAttribute('platform_version'),

                // Device info
                'device' => $visitor->getVisitorAttribute('device'),
                'device_type' => $visitor->getVisitorAttribute('device_type') ?? 'desktop',

                // Device capability flags
                'is_mobile' => $visitor->getVisitorAttribute('is_mobile') === '1',
                'is_tablet' => $visitor->getVisitorAttribute('is_tablet') === '1',
                'is_desktop' => $visitor->getVisitorAttribute('is_desktop') === '1',
                'is_phone' => $visitor->getVisitorAttribute('is_phone') === '1',

                // Bot detection
                'is_bot' => $visitor->getVisitorAttribute('is_bot') === '1',
                'bot_name' => $visitor->getVisitorAttribute('bot_name'),

                // Languages
                'accepted_languages' => $visitor->getVisitorAttribute('accepted_languages'),
            ];
        }

        // Otherwise, parse from user agent
        return $this->parseAgentData($userAgent);
    }

    /**
     * Parse agent data from user agent string
     */
    protected function parseAgentData(?string $userAgent): array
    {
        if (! $userAgent) {
            return $this->getDefaultAgentData();
        }

        $agentService = new AgentService($userAgent);

        return [
            // Browser info
            'browser' => $agentService->getBrowser(),
            'browser_version' => $agentService->getBrowserVersion(),
            'browser_version_major' => $agentService->getBrowserVersionMajor(),

            // Platform/OS info
            'platform' => $agentService->getPlatform(),
            'platform_version' => $agentService->getPlatformVersion(),

            // Device info
            'device' => $agentService->getDevice(),
            'device_type' => $agentService->getDeviceType(),

            // Device capability flags
            'is_mobile' => $agentService->isMobile(),
            'is_tablet' => $agentService->isTablet(),
            'is_desktop' => $agentService->isDesktop(),
            'is_phone' => $agentService->isPhone(),

            // Bot detection
            'is_bot' => $agentService->isBot(),
            'bot_name' => $agentService->getBotName(),

            // Languages
            'accepted_languages' => implode(',', $agentService->getLanguages()),
        ];
    }

    /**
     * Get default agent data when no user agent is available
     */
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

    /**
     * Parse JSON attribute string to array
     */
    protected function parseJsonAttribute($value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }
}
