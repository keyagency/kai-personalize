<?php

namespace KeyAgency\KaiPersonalize\Services;

use Illuminate\Support\Facades\Cache;

class TrackingSignatureService
{
    /**
     * Generate a signature for tracking payload
     */
    public function generate(array $payload, ?string $visitorId = null): string
    {
        $secret = $this->getSecret();

        if (empty($secret)) {
            return '';
        }

        // Add timestamp to prevent replay attacks
        $payload['timestamp'] = now()->timestamp;

        if ($visitorId) {
            $payload['visitor_id'] = $visitorId;
        }

        // Sort keys for consistent hashing
        ksort($payload);

        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $payloadString, $secret);
    }

    /**
     * Verify a signature for tracking payload
     */
    public function verify(array $payload, string $signature, ?string $receivedAt = null): bool
    {
        $secret = $this->getSecret();

        // If no secret configured, skip validation (development mode)
        if (empty($secret)) {
            return true;
        }

        // Check timestamp to prevent replay attacks
        $timestamp = $payload['timestamp'] ?? null;

        if (! $timestamp) {
            return false;
        }

        $ttl = config('kai-personalize.tracking.signature_ttl', 300);
        $now = $receivedAt ? strtotime($receivedAt) : now()->timestamp;

        // Reject if too old or in the future (with 30 second clock skew allowance)
        if ($timestamp < $now - $ttl || $timestamp > $now + 30) {
            return false;
        }

        // Check for replay attacks (already used this timestamp)
        $nonceKey = $payload['nonce'] ?? null;
        if ($nonceKey) {
            $cacheKey = "kai:signature:{$nonceKey}";
            if (Cache::has($cacheKey)) {
                return false; // Already used
            }
            // Mark as used for TTL duration
            Cache::put($cacheKey, true, $ttl);
        }

        // Generate expected signature
        ksort($payload);
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $payloadString, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Generate a nonce for replay attack protection
     */
    public function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get the signature secret from config
     */
    protected function getSecret(): string
    {
        return config('kai-personalize.tracking.signature_secret', '');
    }

    /**
     * Check if signature validation is enabled
     */
    public function isEnabled(): bool
    {
        return ! empty($this->getSecret());
    }
}
