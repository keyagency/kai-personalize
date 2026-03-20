<?php

namespace KeyAgency\KaiPersonalize\Services;

class FingerprintService
{
    /**
     * Generate a fingerprint hash from components
     */
    public function generateHash(array $components): string
    {
        $algorithm = config('kai-personalize.fingerprint.hash_algorithm', 'sha256');
        $data = json_encode($components);

        return hash($algorithm, $data);
    }

    /**
     * Validate fingerprint components
     */
    public function validateComponents(array $components): bool
    {
        $requiredComponents = ['userAgent', 'language', 'timezone'];

        foreach ($requiredComponents as $component) {
            if (! isset($components[$component])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get enabled fingerprint components from config
     */
    public function getEnabledComponents(): array
    {
        return array_filter(
            config('kai-personalize.fingerprint.components', []),
            fn ($enabled) => $enabled === true
        );
    }

    /**
     * Normalize fingerprint data
     */
    public function normalize(array $components): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return trim(strtolower($value));
            }

            return $value;
        }, $components);
    }
}
