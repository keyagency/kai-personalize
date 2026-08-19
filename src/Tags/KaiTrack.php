<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use KeyAgency\KaiPersonalize\ServiceProvider;
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

        $queueSettingsJson = json_encode([
            'threshold' => config('kai-personalize.queue.threshold', 5),
            'sendInterval' => config('kai-personalize.queue.send_interval', 20000),
            'persistQueue' => config('kai-personalize.queue.persist', true),
            'storageKey' => config('kai-personalize.queue.storage_key', 'kai_tracker_queue'),
            'maxEventAge' => config('kai-personalize.queue.max_event_age', 3600000),
        ]);

        $trackerUrl = $this->trackerUrl();

        return <<<JS
<script>
    window.KaiConfig = {
        visitorId: '{$visitorId}',
        sessionId: '{$sessionId}',
        endpoint: '{$endpoint}',
        features: {$featuresJson},
        respectDnt: {$this->boolToString($respectDnt)},
        queueSettings: {$queueSettingsJson},
    };
</script>
<script src="{$trackerUrl}" defer></script>
JS;
    }

    /**
     * URL of the tracker script.
     *
     * Prefer the published copy under public/, which the webserver hands out directly. Falling
     * back to the route means PHP serves an 8 KB static file through the full framework boot -
     * an order of magnitude slower, and a worker occupied per visitor. The version query busts
     * the cache on upgrade, which the route's URL cannot do.
     */
    protected function trackerUrl(): string
    {
        $minified = config('kai-personalize.tracking.use_minified_js', true);
        $file = $minified ? 'tracker.min.js' : 'tracker.js';

        if ($this->publishedTrackerExists($file)) {
            return asset('vendor/kai-personalize/js/'.$file).'?v='.ServiceProvider::VERSION;
        }

        return $minified
            ? route('kai-personalize.tracker-min')
            : route('kai-personalize.tracker');
    }

    /**
     * Whether the tracker has been published, memoized for the request.
     */
    protected function publishedTrackerExists(string $file): bool
    {
        static $published = [];

        return $published[$file] ??= file_exists(public_path('vendor/kai-personalize/js/'.$file));
    }

    protected function boolToString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
