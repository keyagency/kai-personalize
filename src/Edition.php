<?php

namespace KeyAgency\KaiPersonalize;

use Statamic\Facades\Addon;

class Edition
{
    /**
     * Get the current edition (lite or pro)
     */
    public static function get(): string
    {
        return Addon::get('keyagency/kai-personalize')->edition() ?? 'lite';
    }

    /**
     * Check if the current edition is Pro
     */
    public static function isPro(): bool
    {
        return self::get() === 'pro';
    }

    /**
     * Check if the current edition is Lite
     */
    public static function isLite(): bool
    {
        return self::get() === 'lite';
    }

    /**
     * Check if the current edition has a specific feature
     */
    public static function hasFeature(string $feature): bool
    {
        $features = [
            'lite' => [
                'tracking',
                'sessions',
                'basic_rules',
                'geolocation',
                'maxmind',
                'api_connections',
                'behavioral_tracking',
            ],
            'pro' => [
                'tracking',
                'sessions',
                'unlimited_rules',
                'geolocation',
                'maxmind',
                'api_connections',
                'behavioral_tracking',
                'analytics',       // Page analytics dashboard, engagement scoring
                'segments',        // Dynamic visitor segments
                'activecampaign',  // Email campaign integration
                'export',          // Data export functionality
            ],
        ];

        return in_array($feature, $features[self::get()] ?? []);
    }

    /**
     * Get a limit value for the current edition
     */
    public static function getLimit(string $limit): mixed
    {
        $limits = [
            'lite' => [
                'max_rules' => 5,
                'max_api_connections' => 2,
                'max_segments' => 0,
                'retention_days' => 30,
            ],
            'pro' => [
                'max_rules' => null,  // Unlimited
                'max_api_connections' => null,  // Unlimited
                'max_segments' => null,  // Unlimited
                'retention_days' => 365,
            ],
        ];

        return $limits[self::get()][$limit] ?? null;
    }
}
