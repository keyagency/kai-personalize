<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\Log;
use KeyAgency\KaiPersonalize\Models\Rule;
use KeyAgency\KaiPersonalize\Models\Visitor;
use Statamic\Tags\Tags;

class KaiContent extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

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
        $visitorData = $this->getVisitorData();
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

    /**
     * Get current visitor
     */
    protected function getCurrentVisitor(): ?Visitor
    {
        $visitorId = Session::get(config('kai-personalize.session.visitor_id_key'));

        if (! $visitorId) {
            return null;
        }

        return Visitor::findByFingerprint($visitorId);
    }

    /**
     * Get visitor data for evaluation
     */
    protected function getVisitorData(): array
    {
        $visitor = $this->getCurrentVisitor();

        if (! $visitor) {
            return [];
        }

        $currentSession = $visitor->sessions()->latest()->first();

        return [
            'visit_count' => $visitor->visit_count,
            'is_returning' => $visitor->visit_count > 1,
            'country' => $visitor->getVisitorAttribute('country'),
            'city' => $visitor->getVisitorAttribute('city'),
            'region' => $visitor->getVisitorAttribute('region'),
            'browser' => $this->parseBrowser($currentSession?->user_agent),
            'device_type' => $this->detectDeviceType($currentSession?->user_agent),
            'language' => $visitor->getVisitorAttribute('language'),
            'time_of_day' => now()->format('H'),
            'day_of_week' => now()->dayOfWeek,
        ];
    }

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

        return 'Unknown';
    }

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
