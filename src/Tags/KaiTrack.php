<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\Models\Visitor;
use Statamic\Tags\Tags;

class KaiTrack extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class
    // It does not need a $handle property as it's not registered as a standalone tag

    /**
     * {{ kai:track }}
     * Outputs inline JavaScript for client-side behavioral tracking
     */
    public function track(): string
    {
        if (! config('kai-personalize.features.behavioral_tracking')) {
            return '';
        }

        // Get visitor and session IDs from server-side tracking
        $visitorId = Session::get(config('kai-personalize.session.visitor_id_key'));
        $sessionId = Session::get(config('kai-personalize.session.session_id_key'));

        // Fall back to Statamic session if not set
        if (! $sessionId) {
            $sessionId = Session::getId();
        }

        // Generate a temporary fingerprint if visitor not yet tracked
        if (! $visitorId) {
            $visitorId = $this->generateTempFingerprint();
        }

        $endpoint = route('statamic.track');
        $features = [
            'scroll' => config('kai-personalize.features.scroll_tracking', true),
            'click' => config('kai-personalize.features.click_tracking', true),
            'form' => config('kai-personalize.features.form_tracking', false),
            'video' => config('kai-personalize.features.video_tracking', false),
            'fingerprint' => config('kai-personalize.features.fingerprinting', true),
        ];

        $respectDnt = config('kai-personalize.privacy.respect_dnt', true);

        return $this->renderTrackingScript($visitorId, $sessionId, $endpoint, $features, $respectDnt);
    }

    protected function generateTempFingerprint(): string
    {
        return hash('sha256', request()->ip().request()->userAgent().time());
    }

    protected function renderTrackingScript(string $visitorId, string $sessionId, string $endpoint, array $features, bool $respectDnt): string
    {
        $featuresJson = json_encode($features);
        $trackerUrl = route('kai-personalize.tracker');

        // Queue settings from config
        $queueSettingsJson = json_encode([
            'threshold' => config('kai-personalize.queue.threshold', 5),
            'sendInterval' => config('kai-personalize.queue.send_interval', 20000),
            'persistQueue' => config('kai-personalize.queue.persist', true),
            'storageKey' => config('kai-personalize.queue.storage_key', 'kai_tracker_queue'),
            'maxEventAge' => config('kai-personalize.queue.max_event_age', 3600000),
        ]);

        return <<<JS
<script>
    // Kai Personalize - Tracker Configuration
    window.KaiConfig = {
        visitorId: '{$visitorId}',
        sessionId: '{$sessionId}',
        endpoint: '{$endpoint}',
        features: {$featuresJson},
        respectDnt: {$this->boolToString($respectDnt)},
        queueSettings: {$queueSettingsJson},
    };
</script>
<script src="{$trackerUrl}"></script>
JS;
    }

    protected function boolToString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
