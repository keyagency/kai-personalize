<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\Visitor;
use Statamic\Tags\Tags;

class KaiCondition extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

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

        $visitorData = $this->getVisitorData();
        $actualValue = data_get($visitorData, $attribute);

        return $this->evaluateCondition($actualValue, $operator, $value);
    }

    /**
     * Get current visitor data
     */
    protected function getVisitorData(): array
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

        // Build visitor data array
        return [
            'visit_count' => $visitor->visit_count,
            'is_returning' => $visitor->visit_count > 1,
            'country' => $visitor->getVisitorAttribute('country'),
            'city' => $visitor->getVisitorAttribute('city'),
            'region' => $visitor->getVisitorAttribute('region'),
            'browser' => $this->parseBrowser($currentSession?->user_agent),
            'device_type' => $this->detectDeviceType($currentSession?->user_agent),
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

    /**
     * Evaluate condition
     */
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

    /**
     * Parse browser from user agent
     */
    protected function parseBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }
        if (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }
        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            return 'Opera';
        }

        return 'Unknown';
    }

    /**
     * Detect device type from user agent
     */
    protected function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
            return 'mobile';
        }
        if (str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
