<?php

return [
    // Master enable/disable switch
    'enabled' => env('KAI_ENABLED', true),

    // Localization
    'locales' => [
        'default' => 'en',
        'available' => ['en', 'nl'],
        'fallback' => 'en',
    ],

    // Feature toggles
    'features' => [
        'fingerprinting' => env('KAI_PERSONALIZE_FINGERPRINTING', true),
        'ip_tracking' => env('KAI_PERSONALIZE_IP_TRACKING', true),
        'geolocation' => env('KAI_PERSONALIZE_GEOLOCATION', true),
        'behavioral_tracking' => env('KAI_PERSONALIZE_BEHAVIORAL_TRACKING', true),
        'external_data' => env('KAI_PERSONALIZE_EXTERNAL_DATA', true),
        'page_view_tracking' => env('KAI_PERSONALIZE_PAGE_VIEW_TRACKING', true),
        'activecampaign' => env('KAI_ACTIVECAMPAIGN_ENABLED', false),
        // JavaScript client-side tracking features
        'scroll_tracking' => env('KAI_PERSONALIZE_SCROLL_TRACKING', true),
        'click_tracking' => env('KAI_PERSONALIZE_CLICK_TRACKING', true),
        'form_tracking' => env('KAI_PERSONALIZE_FORM_TRACKING', false),
        'video_tracking' => env('KAI_PERSONALIZE_VIDEO_TRACKING', false),
    ],

    // Privacy settings
    'privacy' => [
        'encrypt_ip' => true,
        'anonymize_after_days' => 30,
        'respect_dnt' => true,
        'gdpr_mode' => false,
        'cookie_consent_required' => false,
    ],

    // Tracking security
    'tracking' => [
        // Secret key for HMAC signature validation (generate with: php artisan key:generate --show)
        // If empty, signature validation is disabled (not recommended for production)
        'signature_secret' => env('KAI_TRACKING_SECRET', ''),
        // Signature expires after this many seconds (prevents replay attacks)
        'signature_ttl' => 300, // 5 minutes
        // Allowed origins for tracking requests (prevents cross-origin attacks)
        // Use wildcard for subdomains: ['*.yourdomain.com']
        // Empty array allows all origins (not recommended for production)
        'allowed_origins' => env('KAI_TRACKING_ALLOWED_ORIGINS')
            ? explode(',', env('KAI_TRACKING_ALLOWED_ORIGINS'))
            : [],
        // Use minified JavaScript for tracker (recommended for production)
        'use_minified_js' => env('KAI_USE_MINIFIED_JS', true),
    ],

    // Session configuration (extends Laravel session)
    'session' => [
        'key_prefix' => 'kai_', // Prefix for all session keys
        'visitor_id_key' => 'kai_visitor_id',
        'session_id_key' => 'kai_session_id',
        'use_fingerprint_fallback' => true,
        'sync_with_statamic_session' => true, // Use Statamic's session system
    ],

    // Fingerprint configuration
    'fingerprint' => [
        'enabled' => true,
        'components' => [
            'canvas' => true,
            'webgl' => true,
            'audio' => true,
            'fonts' => true,
            'plugins' => false,
        ],
        'hash_algorithm' => 'sha256',
    ],

    // Data retention
    'retention' => [
        'visitor_data_days' => 365,
        'session_data_days' => 90,
        'log_data_days' => 30,
        'page_view_data_days' => 90,
        'event_data_days' => 30,
    ],

    // MaxMind GeoIP2 Local Database Configuration
    // Download databases from: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
    'maxmind' => [
        'enabled' => env('KAI_MAXMIND_ENABLED', true),

        // Database file paths (relative to storage/ or absolute paths)
        // GeoLite2-City.mmdb provides: country, region, city, postal, lat/long, timezone
        'database_city' => env('KAI_MAXMIND_CITY_DB', 'app/geoip/GeoLite2-City.mmdb'),

        // GeoLite2-Country.mmdb provides: country only (smaller, faster)
        'database_country' => env('KAI_MAXMIND_COUNTRY_DB', 'app/geoip/GeoLite2-Country.mmdb'),

        // GeoLite2-ASN.mmdb provides: ISP/organization info (optional)
        'database_asn' => env('KAI_MAXMIND_ASN_DB', 'app/geoip/GeoLite2-ASN.mmdb'),

        // Cache duration in seconds (86400 = 24 hours)
        'cache_duration' => env('KAI_MAXMIND_CACHE', 86400),

        // License key for automatic database updates (optional)
        'license_key' => env('MAXMIND_LICENSE_KEY'),
    ],

    // API configurations
    'apis' => [
        'geolocation' => [
            'provider' => 'ipapi', // ipapi, maxmind, ip2location, ipstack, ipgeolocation
            'api_key' => env('GEOLOCATION_API_KEY'),
            'cache_duration' => 3600,
        ],
        'weather' => [
            'provider' => 'openweather', // openweather, weatherapi, accuweather
            'api_key' => env('WEATHER_API_KEY'),
            'cache_duration' => 1800,
        ],
        'news' => [
            'provider' => 'newsapi', // newsapi, currents
            'api_key' => env('NEWS_API_KEY'),
            'cache_duration' => 900,
        ],
        'exchange' => [
            'provider' => 'exchangerate', // exchangerate, fixer
            'api_key' => env('EXCHANGE_API_KEY'),
            'cache_duration' => 3600,
        ],
        // Custom API connections are managed via Control Panel
        'custom_connections_enabled' => true,
    ],

    // Performance
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 300,
        'queue_enabled' => false,
        'batch_inserts' => true,
        // Maximum events per tracking request to prevent abuse
        'max_events_per_request' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the client-side event queue
    |
    */

    'queue' => [
        // Number of events to trigger auto-send
        'threshold' => env('KAI_QUEUE_THRESHOLD', 5),

        // Interval in milliseconds for periodic sending
        'send_interval' => env('KAI_QUEUE_SEND_INTERVAL', 20000),

        // Enable/disable localStorage persistence
        'persist' => env('KAI_QUEUE_PERSIST', true),

        // localStorage key name
        'storage_key' => env('KAI_QUEUE_STORAGE_KEY', 'kai_tracker_queue'),

        // Maximum age of queued events in milliseconds (0 = no limit)
        'max_event_age' => env('KAI_QUEUE_MAX_EVENT_AGE', 3600000), // 1 hour
    ],

    // Conditions
    'conditions' => [
        'operators' => [
            'equals',
            'not_equals',
            'contains',
            'not_contains',
            'greater_than',
            'less_than',
            'in',
            'not_in',
        ],
        'custom_conditions' => [],
    ],

    // API connection authentication types
    'auth_types' => [
        'none' => 'No Authentication',
        'api_key' => 'API Key (Header or Query)',
        'bearer' => 'Bearer Token',
        'basic' => 'Basic Auth',
        'oauth2' => 'OAuth 2.0',
        'custom' => 'Custom Headers',
    ],

    // ActiveCampaign integration
    'activecampaign' => [
        'enabled' => env('KAI_ACTIVECAMPAIGN_ENABLED', false),
        'api_url' => env('KAI_ACTIVECAMPAIGN_URL'),
        'api_key' => env('KAI_ACTIVECAMPAIGN_API_KEY'),
        'cookie_name' => env('KAI_ACTIVECAMPAIGN_COOKIE', 'vgo_ee'),
        'cache_ttl' => env('KAI_ACTIVECAMPAIGN_CACHE_TTL', 1440), // 24 hours in minutes
    ],

    // Bot blacklist configuration
    'blacklist' => [
        'enabled' => env('KAI_BLACKLIST_ENABLED', false),
        'logging' => env('KAI_BLACKLIST_LOGGING', true),
        'log_retention_days' => env('KAI_BLACKLIST_LOG_RETENTION', 30),
    ],
];
