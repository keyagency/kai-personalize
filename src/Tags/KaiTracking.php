<?php

namespace KeyAgency\KaiPersonalize\Tags;

use KeyAgency\KaiPersonalize\Services\TrackingSignatureService;
use Statamic\Tags\Tags;

class KaiTracking extends Tags
{
    protected static $handle = 'kai_tracking';

    /**
     * Generate tracking signature for client-side requests
     *
     * Usage: {{ kai:tracking }}
     *   Returns: signature, timestamp, nonce, enabled
     *
     * Usage: {{ kai:tracking visitor_id="{visitor_id}" }}
     *   Returns: signature with visitor_id included
     */
    public function index(): array
    {
        $service = app(TrackingSignatureService::class);
        $visitorId = $this->params->get('visitor_id');

        // Generate nonce for replay attack protection
        $nonce = $service->generateNonce();

        // Build payload for signature
        $payload = [
            'nonce' => $nonce,
        ];

        if ($visitorId) {
            $payload['visitor_id'] = $visitorId;
        }

        $signature = $service->generate($payload, $visitorId);

        return [
            'signature' => $signature,
            'nonce' => $nonce,
            'timestamp' => now()->timestamp,
            'enabled' => $service->isEnabled(),
            'visitor_id' => $visitorId,
        ];
    }

    /**
     * Alias for index
     */
    public function signature(): array
    {
        return $this->index();
    }

    /**
     * Get tracking URL
     */
    public function url(): string
    {
        return route('statamic.track');
    }
}
