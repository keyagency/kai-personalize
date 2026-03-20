# Kai Personalize - Statamic Add-on

[![Statamic Marketplace](https://img.shields.io/badge/Statamic-Marketplace-orange.svg)](https://statamic.com/marketplace/addons/kai-personalize)
[![Latest Version](https://img.shields.io/badge/version-v1.3.1-blue.svg)](https://github.com/keyagency/kai-personalize/releases)

**Adaptive content delivery based on visitor attributes and behavior**

## Overview

Kai Personalize is a professional Statamic add-on that enables you to deliver personalized content based on visitor attributes (browser, IP, location, device fingerprint) and external data sources (weather, news, etc.). Built by Key Agency with AI agent Kai.

## Editions

Kai Personalize is available in two editions:

### Free Edition
Perfect for getting started with personalization:

- Visitor tracking & sessions
- Basic rules (max 5 active)
- Geolocation (MaxMind)
- API connections (max 2)
- Behavioral tracking (page views, scroll, clicks)

### Pro Edition
Advanced features for growing businesses:

- **Everything in Free**
- Unlimited rules & API connections
- Analytics dashboard & engagement scoring
- Dynamic segments
- ActiveCampaign integration
- Data export functionality

**Upgrade to Pro** at [statamic.com/marketplace/addons/kai-personalize](https://statamic.com/marketplace/addons/kai-personalize)

### Feature Comparison

| Feature | Free | Pro |
|---------|------|-----|
| Visitor Tracking | ✅ | ✅ |
| Session Management | ✅ | ✅ |
| Browser Detection | ✅ | ✅ |
| Geolocation (MaxMind) | ✅ | ✅ |
| API Connections | 2 max | Unlimited |
| Personalization Rules | 5 max | Unlimited |
| Behavioral Tracking | ✅ | ✅ |
| Analytics Dashboard | ❌ | ✅ |
| Engagement Scoring | ❌ | ✅ |
| Dynamic Segments | ❌ | ✅ |
| ActiveCampaign Integration | ❌ | ✅ |
| Data Export | ❌ | ✅ |

## Current Status

**Version:** v1.3.0 - Production Ready
**Status:** All core features complete and functional. Now compatible with Statamic 6!

### ✅ What's Working Now:
- ✅ Visitor tracking (server-side)
- ✅ Session management
- ✅ Database structure (12 tables)
- ✅ All Antlers tags
- ✅ API services (Weather, Geolocation, Custom)
- ✅ Artisan commands
- ✅ **Complete Control Panel Interface:**
  - ✅ Dashboard with real-time statistics
  - ✅ **Analytics & Engagement Scoring** (NEW)
  - ✅ Rules management (CRUD, condition builder, statistics)
  - ✅ Visitors management (browse, search, profiles, sessions, page history, behavioral summary)
  - ✅ Segments (CRUD, visitor assignment, criteria builder)
  - ✅ API Connections (CRUD, testing, cache management)
  - ✅ Settings page (configuration overview)
- ✅ Privacy features (IP encryption, DNT, GDPR compliance)

### 🔨 Next Up:
- Enhanced dashboard with charts
- Export/Import functionality
- Segment-based condition support in Rules

## Features

- **Browser Detection**: Comprehensive browser, device, and bot detection via [jenssegers/agent](https://github.com/jenssegers/agent)
- **Local Geolocation**: Fast IP-to-location lookups using [MaxMind GeoIP2](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data) local databases (no API calls)
- **Browser Fingerprinting**: Advanced visitor identification using canvas, WebGL, audio, and more
- **Session Management**: Leverages Statamic's built-in session system with visitor tracking
- **Behavioral Tracking**: Monitor page views, time on site, referrers, and UTM parameters
- **External API Integration**: Connect to weather, news, exchange rates, and custom APIs
- **ActiveCampaign Integration**: Automatic email campaign visitor tracking and CRM data sync
- **Rule-Based Personalization**: Create complex conditions to show different content
- **Privacy Compliant**: GDPR support, IP encryption, DNT respect, and data anonymization
- **Multilingual**: Full English and Dutch support
- **Control Panel Interface**: Comprehensive dashboard for managing all aspects
- **Performance Optimized**: Caching, queueing, and batch operations

## Dependencies

- **PHP**: ^8.2
- **Statamic**: ^6.0
- **jenssegers/agent**: ^2.6 - Browser/device detection
- **geoip2/geoip2**: ^3.0 - MaxMind GeoIP2 local database reader

## Quick Start

Once installed, you can immediately:

1. **View visitor data**: Go to `/cp/kai-personalize` in your Control Panel
2. **Use Antlers tags**: Add personalization to your templates (see examples below)
3. **Check settings**: Configure features at `/cp/kai-personalize/settings`

The addon automatically tracks visitors as they browse your site!

## Installation

1. Add the addon to your project:

```bash
composer require keyagency/kai-personalize
```

2. Publish configuration and translations:

```bash
php artisan vendor:publish --tag=kai-personalize-config
php artisan vendor:publish --tag=kai-personalize-translations
```

3. Run migrations:

```bash
php artisan migrate
```

4. Configure your API keys in `.env`:

```env
# External API keys
GEOLOCATION_API_KEY=your_key_here
WEATHER_API_KEY=your_key_here
NEWS_API_KEY=your_key_here
EXCHANGE_API_KEY=your_key_here

# ActiveCampaign integration (optional)
KAI_ACTIVECAMPAIGN_ENABLED=true
KAI_ACTIVECAMPAIGN_URL=https://your-account.api-us1.com
KAI_ACTIVECAMPAIGN_API_KEY=your_api_key_here
KAI_ACTIVECAMPAIGN_COOKIE=vgo_ee
KAI_ACTIVECAMPAIGN_CACHE_TTL=1440

# Tracker Queue Settings (optional)
KAI_QUEUE_THRESHOLD=5
KAI_QUEUE_SEND_INTERVAL=20000
KAI_QUEUE_PERSIST=true
KAI_QUEUE_STORAGE_KEY=kai_tracker_queue
KAI_QUEUE_MAX_EVENT_AGE=3600000

# Security (optional but recommended for production)
KAI_TRACKING_SECRET=your-unique-secret-key-here
KAI_TRACKING_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

5. (Optional) Set up MaxMind GeoIP2 for local geolocation:

```bash
# Create the geoip directory
mkdir -p storage/app/geoip

# Download free GeoLite2 databases from:
# https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
# Place the .mmdb files in storage/app/geoip/
```

## Configuration

The configuration file is located at `config/kai-personalize.php`. Key settings include:

```php
'features' => [
    'fingerprinting' => true,
    'ip_tracking' => true,
    'geolocation' => true,
    'behavioral_tracking' => true,
    'external_data' => true,
],

'privacy' => [
    'encrypt_ip' => true,
    'anonymize_after_days' => 30,
    'respect_dnt' => true,
    'gdpr_mode' => false,
],

// MaxMind GeoIP2 Local Database (no API calls needed)
'maxmind' => [
    'enabled' => true,
    'database_city' => 'app/geoip/GeoLite2-City.mmdb',
    'database_country' => 'app/geoip/GeoLite2-Country.mmdb',
    'database_asn' => 'app/geoip/GeoLite2-ASN.mmdb',
    'cache_duration' => 86400,
],
```

## MaxMind GeoIP2 (Local Geolocation)

The addon uses MaxMind GeoIP2 for fast, local geolocation lookups without API calls.

### Setup

1. Create a free MaxMind account at https://www.maxmind.com/en/geolite2/signup
2. Generate a license key in your MaxMind account
3. Download the databases using the Artisan command:

```bash
# Download all databases
php artisan kai:maxmind:download --license=YOUR_LICENSE_KEY

# Or set the license key in .env and run without option
# MAXMIND_LICENSE_KEY=your_license_key
php artisan kai:maxmind:download

# Download only specific database
php artisan kai:maxmind:download --database=city
php artisan kai:maxmind:download --database=country
php artisan kai:maxmind:download --database=asn
```

**Available databases:**
- **GeoLite2-City.mmdb** - Full location data (country, region, city, postal, timezone, coordinates)
- **GeoLite2-Country.mmdb** - Country only (smaller, faster)
- **GeoLite2-ASN.mmdb** - ISP/organization info (optional)

The command downloads and extracts the `.mmdb` files to `storage/app/geoip/`

### Configuration

```env
# Enable/disable MaxMind
KAI_MAXMIND_ENABLED=true

# Database paths (relative to storage/)
KAI_MAXMIND_CITY_DB=app/geoip/GeoLite2-City.mmdb
KAI_MAXMIND_COUNTRY_DB=app/geoip/GeoLite2-Country.mmdb
KAI_MAXMIND_ASN_DB=app/geoip/GeoLite2-ASN.mmdb

# Cache duration in seconds (default: 24 hours)
KAI_MAXMIND_CACHE=86400

# Optional: License key for automatic updates
MAXMIND_LICENSE_KEY=your_license_key
```

### Stored Attributes

When enabled, MaxMind stores these visitor attributes (type: `external`):

| Attribute | Example | Database |
|-----------|---------|----------|
| `country` | Netherlands | City/Country |
| `country_code` | NL | City/Country |
| `region` | North Holland | City |
| `region_code` | NH | City |
| `city` | Amsterdam | City |
| `postal_code` | 1012 | City |
| `continent` | Europe | City/Country |
| `continent_code` | EU | City/Country |
| `timezone` | Europe/Amsterdam | City |
| `is_eu` | 1 | City/Country |
| `latitude` | 52.3676 | City |
| `longitude` | 4.9041 | City |
| `isp` | KPN B.V. | ASN |

> **Note:** Coordinates are not stored when `gdpr_mode` is enabled.

## ActiveCampaign Integration

The addon integrates with ActiveCampaign to automatically identify visitors from email campaigns and personalize content based on their CRM data.

### How It Works

1. User clicks link in ActiveCampaign email → lands on site with tracking cookie (`vgo_ee`, `__actc`, etc.)
2. TrackVisitor middleware detects the AC cookie
3. Server-side API call fetches contact data (tags, lists, custom fields)
4. Data stored as visitor attributes (type: `crm`)
5. Available via `{{ kai:visitor }}` tag for personalization

### Configuration

Add these settings to your `.env` file:

```env
# Enable ActiveCampaign integration
KAI_ACTIVECAMPAIGN_ENABLED=true

# ActiveCampaign API credentials
KAI_ACTIVECAMPAIGN_URL=https://your-account.api-us1.com
KAI_ACTIVECAMPAIGN_API_KEY=your_api_key_here

# Cookie name that contains the email (ActiveCampaign default)
KAI_ACTIVECAMPAIGN_COOKIE=vgo_ee

# Cache duration in minutes (default: 1440 = 24 hours)
KAI_ACTIVECAMPAIGN_CACHE_TTL=1440
```

### Stored Attributes

When a visitor arrives from an ActiveCampaign email, these attributes are stored (type: `crm`):

| Attribute | Type | Description |
|-----------|------|-------------|
| `ac_contact_id` | string | ActiveCampaign contact ID |
| `ac_email` | string | Contact email address |
| `ac_first_name` | string | First name |
| `ac_last_name` | string | Last name |
| `ac_phone` | string | Phone number |
| `ac_tags` | json | Array of tag names |
| `ac_lists` | json | Object with list membership status |
| `ac_custom_fields` | json | Custom field values |
| `ac_created_at` | timestamp | Account created date |
| `ac_updated_at` | timestamp | Last updated in AC |

### Usage Examples

#### Personalize by Tag

```antlers
{{ kai:visitor }}
    {{ if ac_tags contains 'VIP' }}
        <p>Welcome back, VIP member! Here's your exclusive content.</p>
    {{ /if }}
{{ /kai:visitor }}
```

#### Personalize by List Membership

```antlers
{{ kai:visitor }}
    {{ if ac_lists.newsletter.status == 1 }}
        <p>Thanks for being a subscriber!</p>
    {{ /if }}
{{ /kai:visitor }}
```

#### Personalize by Custom Field

```antlers
{{ kai:visitor }}
    {{ if ac_custom_fields.member_level == 'Gold' }}
        <p>Gold member exclusive benefits</p>
    {{ /if }}
{{ /kai:visitor }}
```

#### Condition Tag with AC Data

```antlers
{{ kai:condition attribute="ac_member_level" operator="equals" value="Gold" }}
    <p>Gold member exclusive content</p>
{{ /kai:condition }}
```

### Testing

Test the ActiveCampaign integration via command line:

```bash
# Test API connection
php artisan kai:test-activecampaign

# Test email lookup
php artisan kai:test-activecampaign --email=user@example.com

# Test cookie-based retrieval (interactive)
php artisan kai:test-activecampaign --test-cookie
```

### Cookie Decoding

The service automatically handles multiple ActiveCampaign cookie encoding formats:
- Base64 encoded email
- URL-encoded + Base64
- Plain text email
- URL-encoded email

The default cookie name is `vgo_ee` but can be configured via `KAI_ACTIVECAMPAIGN_COOKIE`. Alternative cookies (`__actc`, `contact_email`) are checked as fallbacks.

### Privacy & GDPR

1. **Cookie consent** - Only reads AC cookie if consent given (when `cookie_consent_required` is enabled)
2. **Data retention** - Cache TTL respects AC rate limits (default 24 hours)
3. **Right to be forgotten** - AC attributes are cleared when visitor data is deleted
4. **Logging** - API calls are logged but sensitive data is masked

## Browser Detection

The addon uses [jenssegers/agent](https://github.com/jenssegers/agent) for comprehensive browser and device detection.

### Stored Attributes

These attributes are automatically stored for each visitor (type: `technical`):

| Attribute | Example | Description |
|-----------|---------|-------------|
| `browser` | Chrome | Browser name |
| `browser_version` | 120.0.0.0 | Full version string |
| `browser_version_major` | 120 | Major version number |
| `platform` | OS X | Operating system |
| `platform_version` | 10_15_7 | OS version |
| `device` | Macintosh | Device name |
| `device_type` | desktop | mobile, tablet, or desktop |
| `is_mobile` | 0 | Is mobile device (includes tablets) |
| `is_tablet` | 0 | Is tablet |
| `is_desktop` | 1 | Is desktop |
| `is_phone` | 0 | Is phone (mobile but not tablet) |
| `is_bot` | 0 | Is bot/crawler |
| `bot_name` | Googlebot | Bot name (if detected) |
| `accepted_languages` | en,nl | From Accept-Language header |

### Conditional Content by Browser/Device

```antlers
{{# Show different content for mobile users #}}
{{ kai:condition attribute="is_mobile" operator="equals" value="1" }}
    <a href="tel:+31201234567">Call us</a>
{{ /kai:condition }}

{{# Target specific browsers #}}
{{ kai:condition attribute="browser" operator="equals" value="Safari" }}
    <p>You're using Safari!</p>
{{ /kai:condition }}

{{# Hide content from bots #}}
{{ kai:condition attribute="is_bot" operator="equals" value="0" }}
    <div class="tracking-pixel">...</div>
{{ /kai:condition }}
```

## Antlers Tags

### kai:visitor

Get information about the current visitor:

```antlers
{{ kai:visitor }}
    {{# Basic info #}}
    {{ fingerprint }}
    {{ session_id }}
    {{ ip_address }}
    {{ visit_count }}
    {{ is_returning }}
    {{ first_visit }}
    {{ last_visit }}

    {{# Browser info (via jenssegers/agent) #}}
    {{ browser }}              {{# Chrome, Firefox, Safari, Edge, etc. #}}
    {{ browser_version }}      {{# Full version: 120.0.0.0 #}}
    {{ browser_version_major }} {{# Major version: 120 #}}

    {{# Platform/OS info #}}
    {{ platform }}             {{# Windows, OS X, Linux, Android, iOS #}}
    {{ platform_version }}     {{# OS version #}}

    {{# Device info #}}
    {{ device }}               {{# iPhone, iPad, Macintosh, etc. #}}
    {{ device_type }}          {{# mobile, tablet, desktop #}}
    {{ is_mobile }}            {{# true/false #}}
    {{ is_tablet }}            {{# true/false #}}
    {{ is_desktop }}           {{# true/false #}}
    {{ is_phone }}             {{# true/false (mobile but not tablet) #}}

    {{# Bot detection #}}
    {{ is_bot }}               {{# true/false #}}
    {{ bot_name }}             {{# Googlebot, bingbot, etc. #}}

    {{# Geolocation (via MaxMind) #}}
    {{ country }}
    {{ country_code }}
    {{ region }}
    {{ city }}
    {{ postal_code }}
    {{ timezone }}
    {{ continent }}
    {{ is_eu }}

    {{# Traffic source #}}
    {{ referrer }}
    {{ utm_source }}
    {{ utm_medium }}
    {{ utm_campaign }}

    {{# Languages #}}
    {{ language }}
    {{ accepted_languages }}

    {{# ActiveCampaign (if enabled and visitor from email) #}}
    {{ ac_contact_id }}
    {{ ac_email }}
    {{ ac_first_name }}
    {{ ac_last_name }}
    {{ ac_phone }}
    {{ ac_tags }}            {{# Array of tag names #}}
    {{ ac_lists }}           {{# Object with list status #}}
    {{ ac_custom_fields }}   {{# Custom field values #}}
    {{ ac_created_at }}
    {{ ac_updated_at }}
{{ /kai:visitor }}
```

### kai:condition

Show content based on conditions:

```antlers
{{ kai:condition attribute="country" operator="equals" value="US" }}
    <p>Content for US visitors</p>
{{ /kai:condition }}

{{ kai:condition attribute="device_type" operator="equals" value="mobile" }}
    <p>Mobile-specific content</p>
{{ /kai:condition }}

{{ kai:condition attribute="visit_count" operator="greater_than" value="5" }}
    <p>Welcome back, loyal visitor!</p>
{{ /kai:condition }}
```

### kai:external

Fetch data from external APIs:

```antlers
{{# Weather API #}}
{{ kai:external source="weather" location="Amsterdam" }}
    <p>Temperature: {{ temperature }}°C</p>
    <p>Condition: {{ condition }}</p>
{{ /kai:external }}

{{# Geolocation #}}
{{ kai:external source="geolocation" }}
    <p>You are in {{ city }}, {{ country }}</p>
{{ /kai:external }}

{{# Custom API #}}
{{ kai:external
    source="custom"
    connection="my-api"
    endpoint="/data"
    params:id="123"
}}
    {{ response_data }}
{{ /kai:external }}
```

### kai:content

Display content based on rules:

```antlers
{{ kai:content rules="homepage-hero" fallback="default" }}
    {{ if condition_met }}
        <h1>Personalized Hero Content</h1>
    {{ else }}
        <h1>Default Hero Content</h1>
    {{ /if }}
{{ /kai:content }}
```

### kai:session

Manage session data:

```antlers
{{# Set session data #}}
{{ kai:session:set key="preference" value="dark_mode" }}

{{# Get session data #}}
{{ kai:session:get key="preference" }}

{{# Check if visitor is tracked #}}
{{ if {kai:session:tracked} }}
    <p>We know you!</p>
{{ /if }}
```

### kai:api

Make direct API calls with caching:

```antlers
{{ kai:api
    url="https://api.example.com/data"
    method="GET"
    cache="600"
    params:category="news"
}}
    {{ results }}
{{ /kai:api }}
```

### kai:track

Outputs the client-side tracking script for behavioral analytics. This enables automatic tracking of user interactions without requiring manual event tagging.

#### Usage

Add to your main layout file (typically in `<head>` or before closing `</body>`):

```antlers
{{! In your layout file, e.g., resources/views/layouts/layout.antlers.html }}
<!DOCTYPE html>
<html>
<head>
    {{! Other head content }}
    {{ kai:track }}
</head>
<body>
    {{! Or place it before closing body }}
    {{ kai:track }}
</body>
</html>
```

#### What It Tracks

When enabled, the tracker automatically captures:

| Feature | Description |
|---------|-------------|
| **Page Views** | URL, title, referrer, screen dimensions |
| **Scroll Depth** | Thresholds: 25%, 50%, 75%, 90%, 100% |
| **Reading Time** | Active reading time per page |
| **Clicks** | All clicks with element, position, and hesitation time |
| **Rage Clicks** | 3+ clicks on same element within 2 seconds |
| **Dead Clicks** | Clicks on non-interactive elements |
| **Visibility** | Page visibility changes (hidden/visible/pagehide) |
| **Exit Intent** | Mouse leaving viewport (potential exit) |
| **Idle Detection** | No activity for 60+ seconds |
| **Device** | Viewport, screen, touch, connection info |
| **Preferences** | Dark mode, reduced motion, language, timezone |
| **Fingerprint** | Browser fingerprint (canvas, WebGL, audio) |

#### Configuration

Features are controlled via `config/kai-personalize.php`:

```php
'features' => [
    'scroll_tracking' => true,   // Scroll depth, reading time, exit intent
    'click_tracking' => true,   // Clicks, rage clicks, dead clicks, hesitation
    'form_tracking' => false,   // Form interactions
    'video_tracking' => false,  // Video engagement
    'fingerprinting' => true,   // Browser fingerprinting
],
```

#### Master Switch

To completely disable tracking:

```env
KAI_PERSONALIZE_ENABLED=false
```

Or in config:

```php
'enabled' => env('KAI_PERSONALIZE_ENABLED', true),
```

When disabled, the tag outputs nothing.

#### Privacy

The tracker respects:
- **DNT header** - Stops tracking if `navigator.doNotTrack === '1'`
- **Cookie consent** - Checks for common consent cookie implementations
- **Custom consent callback** - Use `window.KaiConsentCallback` function for custom logic

#### JavaScript API

The tracker exposes a global API for manual control:

```javascript
// Check if tracking is enabled
if (window.KaiTracker.hasConsent()) {
    // Manual event tracking
    window.KaiTracker.track('custom_event', { my_data: 'value' });

    // Force send queued events
    window.KaiTracker.send();
}
```

#### How It Works

1. The tag outputs a config script with visitor/session IDs
2. Loads `tracker.js` from `/kai-personalize/tracker.js`
3. Events are queued and batched (configurable threshold and interval)
4. Queue is persisted to localStorage (survives page refreshes)
5. Uses `sendBeacon` for reliable delivery on page unload
6. Cached for 1 day on the client

#### Queue Configuration

The tracker uses intelligent queue management to ensure reliable event delivery:

| Setting | Default | Description |
|---------|---------|-------------|
| `threshold` | 5 events | Auto-send when queue reaches this size |
| `sendInterval` | 20000 ms (20s) | Periodic send interval |
| `persistQueue` | true | Enable localStorage persistence |
| `storageKey` | `kai_tracker_queue` | localStorage key name |
| `maxEventAge` | 3600000 ms (1h) | Maximum event age before discarding |

**Configure via `.env`:**
```env
KAI_QUEUE_THRESHOLD=5              # Send after 5 events
KAI_QUEUE_SEND_INTERVAL=20000      # Send every 20 seconds
KAI_QUEUE_PERSIST=true             # Persist to localStorage
KAI_QUEUE_STORAGE_KEY=kai_tracker_queue
KAI_QUEUE_MAX_EVENT_AGE=3600000    # Discard events older than 1 hour
```

**localStorage Persistence:**
- Events are saved to localStorage as they're queued
- Survives page refreshes and navigation
- Automatically restored on page load
- Stale events (older than `maxEventAge`) are discarded
- Gracefully handles quota exceeded errors

### kai:behavior

Get behavioral statistics for the current visitor:

```antlers
{{ kai:behavior }}
    {{ max_scroll_depth }}
    {{ total_reading_time_ms }}
    {{ total_clicks }}
    {{ total_events }}
{{ /kai:behavior }}
```

### kai:tracking

Generates cryptographic signatures for secure tracking endpoint validation. **Only required when `KAI_TRACKING_SECRET` is configured.**

#### Usage

When HMAC signature validation is enabled, use this tag to generate a signature for the tracking endpoint:

```antlers
{{ kai:tracking }}
    {{ signature }}        {{! HMAC SHA-256 signature }}
    {{ nonce }}            {{! Unique nonce for replay protection }}
    {{ timestamp }}        {{! Current Unix timestamp }}
    {{ enabled }}          {{! Whether signature validation is enabled }}
{{ /kai:tracking }}
```

#### JavaScript Integration

The signature must be included with tracking requests:

```javascript
// Get signature from kai:tracking tag (mounted on window)
fetch(window.KaiTracking.endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        visitor_id: window.KaiConfig.visitorId,
        session_id: window.KaiConfig.sessionId,
        events: events,
        signature: window.KaiTracking.signature,
        nonce: window.KaiTracking.nonce,
        timestamp: window.KaiTracking.timestamp
    })
});
```

#### When to Use

- **Required** when `KAI_TRACKING_SECRET` is set in `.env`
- **Not needed** for basic tracking without signature validation
- The `{{ kai:track }}` tag automatically handles this internally

## Installation - CSRF Exceptions

When using the tracking endpoint, you must add CSRF token exceptions to `bootstrap/app.php`:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'kai-personalize/track',      // Main tracking endpoint
        'kai-personalize/*',          // All Kai Personalize routes
    ]);
})
```

This allows the tracking JavaScript to POST events without CSRF tokens.

## API Connections

### Built-in Providers

The addon supports these built-in API providers:

**Weather APIs:**
- OpenWeatherMap
- WeatherAPI
- AccuWeather

**Geolocation APIs:**
- IPapi
- MaxMind GeoIP2
- IP2Location
- ipstack

### Custom API Connections

Manage your external API connections via the Control Panel:

1. Navigate to **Kai Personalize > API Connections**
2. Click **Create Connection**
3. Fill in the details:
   - Name
   - Provider type (Weather, Geolocation, News, Exchange, Custom)
   - API URL
   - Authentication (None, API key, Bearer, Basic, OAuth2, Custom)
   - Rate limits
   - Cache duration
   - Custom headers (optional)
4. **Test** the connection to ensure it works
5. **View statistics**: Total requests, success rate, cache usage
6. **Manage cache**: Clear cached responses when needed

### Testing Connections

Test API connections from the command line:

```bash
php artisan kai:test-api connection-name
```

## Artisan Commands

```bash
# Download MaxMind GeoLite2 databases
php artisan kai:maxmind:download --license=YOUR_LICENSE_KEY
php artisan kai:maxmind:download --database=city  # Download only city database
php artisan kai:maxmind:download                  # Uses MAXMIND_LICENSE_KEY from .env

# Test MaxMind database lookup
php artisan kai:maxmind:test                      # Test with default IP (95.97.1.234)
php artisan kai:maxmind:test 8.8.8.8              # Test with specific IP
php artisan kai:maxmind:test --info               # Show database info only

# Clean up old visitor data
php artisan kai:cleanup --days=30

# Test an API connection
php artisan kai:test-api my-connection

# Refresh API cache
php artisan kai:refresh-cache --all
php artisan kai:refresh-cache connection-name

# Prune old API logs
php artisan kai:prune-logs --days=30

# Test ActiveCampaign integration
php artisan kai:test-activecampaign                # Test connection
php artisan kai:test-activecampaign --email=user@example.com  # Test email lookup
php artisan kai:test-activecampaign --test-cookie # Test cookie retrieval
```

## Database Structure

The addon uses the following tables (all prefixed with `kai_personalize_`):

- **visitors** - Unique visitors identified by fingerprint
- **visitor_sessions** - Individual browsing sessions
- **visitor_attributes** - Custom visitor attributes
- **page_views** - Page view tracking with entry metadata
- **events** - Behavioral events (scroll depth, clicks, reading time, etc.)
- **rules** - Personalization rules and conditions
- **segments** - Visitor segments with criteria
- **segment_visitor** - Pivot table for visitor-segment relationships
- **logs** - Personalization event logs
- **api_connections** - External API configurations
- **api_cache** - Cached API responses
- **api_logs** - API request logs

## Privacy & GDPR

The addon includes several privacy features:

- **IP Encryption**: Automatically encrypt stored IP addresses
- **Do Not Track**: Respect DNT browser headers
- **Data Anonymization**: Automatically anonymize data after specified period
- **GDPR Mode**: Additional privacy controls for EU compliance
- **Cookie Consent**: Optional cookie consent requirement
- **Data Retention**: Configurable retention periods for all data types
- **Right to be Forgotten**: Delete visitor data via CP or API

## Tracking Security

The addon includes multiple layers of protection to prevent data pollution and abuse:

### Built-in Protections

| Protection | Description | Default |
|-----------|-------------|---------|
| **Rate Limiting** | Max 60 requests/minute, 500/hour per IP | ✅ Enabled |
| **Input Sanitization** | Event types validated, HTML stripped, whitelist keys | ✅ Enabled |
| **Max Events** | Maximum 50 events per request | ✅ Enabled |
| **Event Type Regex** | Only alphanumeric + underscore allowed | ✅ Enabled |
| **HMAC Signatures** | Cryptographic validation of tracking requests | ⚠️ Optional |
| **Timestamp Validation** | Rejects expired signatures (5 min) | ⚠️ Optional |
| **Replay Protection** | Nonce caching prevents duplicate requests | ⚠️ Optional |
| **Origin Validation** | Whitelist allowed domains | ⚠️ Optional |

### Enabling HMAC Signature Validation (Recommended for Production)

To enable cryptographic signature validation, configure a secret key:

```env
# Generate a unique 32+ character string
KAI_TRACKING_SECRET=your-unique-secret-key-here

# Optional: Restrict to your domains (comma-separated)
KAI_TRACKING_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com,*.yourdomain.com
```

### How Signature Validation Works

1. **Server generates signature** using the `{{ kai:tracking }}` tag:
   ```antlers
   {{ kai:tracking }}
       signature: "abc123..."
       nonce: "def456..."
       timestamp: 1738492800
       enabled: true
   {{ /kai:tracking }}
   ```

2. **Client includes signature** with tracking requests:
   ```javascript
   fetch('/kai-personalize/track', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({
           visitor_id: '...',
           session_id: '...',
           events: [...],
           signature: '...',    // From server
           nonce: '...',         // From server
           timestamp: 1738492800 // From server
       })
   });
   ```

3. **Server verifies** before processing:
   - Signature matches (HMAC SHA-256)
   - Timestamp is recent (within 5 minutes)
   - Nonce hasn't been used before

### Security Best Practices

1. **Always use HTTPS** - Signatures can be intercepted over HTTP
2. **Generate a strong secret** - Use `php artisan key:generate --show`
3. **Set allowed origins** - Restricts cross-origin requests
4. **Monitor logs** - Failed signature attempts are logged
5. **Use a WAF** - CloudFlare or similar for DDoS protection

## Performance

The addon is optimized for performance:

- **Caching**: Redis/file-based caching for API responses and visitor data
- **Queueing**: Optional queue support for heavy operations
- **Batch Operations**: Efficient bulk inserts and updates
- **CDN-Friendly**: Static asset delivery
- **Rate Limiting**: Prevent API overuse
- **Circuit Breaker**: Graceful degradation when APIs fail

## Multilingual Support

The addon is fully translated in:
- English (en)
- Dutch (nl)

All Control Panel text, error messages, and documentation are available in both languages. The addon automatically detects the current site locale.

## Control Panel

Access the Control Panel interface at `/cp/kai-personalize`:

### ✅ Fully Implemented:
- **Dashboard**: Overview of visitors, sessions, top pages, and top engaged visitors
- **Analytics**: Page-level analytics with views, unique visitors, scroll depth, and reading time
- **Rules**: Create and manage personalization rules with condition builder
- **Visitors**: Browse visitor profiles with engagement scores, page history, behavioral summary, sessions, and attributes
- **Segments**: Create dynamic visitor segments with criteria-based assignment
- **API Connections**: Manage external API integrations with testing and cache management
- **Settings**: Configure features, privacy, and performance

### Analytics & Engagement Scoring

The addon now includes comprehensive analytics and engagement tracking:

#### Engagement Score (0-100)
Each visitor receives an engagement score based on:
- **Visit Frequency** (0-30 points): `visit_count × 3`, max 30
- **Page Views** (0-25 points): `page_views × 2`, max 25
- **Reading Time** (0-25 points): For every 10 seconds of reading time, 1 point, max 25
- **Scroll Depth** (0-20 points): Max scroll depth / 5, max 20

Color-coded badges:
- 🟢 Green (70-100): Highly engaged
- 🟡 Yellow (40-69): Moderately engaged
- ⚪ Gray (0-39): Low engagement

#### Behavioral Summary
For each visitor, track:
- **Max Scroll Depth**: Deepest scroll percentage recorded
- **Reading Time**: Total time spent reading (in minutes)
- **Total Clicks**: Number of click events tracked
- **Total Events**: All behavioral events combined

#### Page Analytics
Navigate to **Analytics > Pages** to see:
- Total views per page
- Unique visitors per page
- First and last view timestamps
- Average scroll depth
- Average reading time
- Recent views with visitor links

#### Visitor Page History
Each visitor profile now includes:
- Complete browsing history with pagination
- Entry title and collection
- URL path
- View timestamp

### Available Event Types

The addon tracks these behavioral events via the `kai:track` tag:
- `scroll_depth` - Maximum scroll percentage on a page
- `click` - Click events on elements
- `visibility` - Element visibility tracking
- `reading_time` - Time spent reading content
- `custom` - Custom events

## Example Use Cases

### Weather-Based Content

```antlers
{{ kai:external source="weather" }}
    {{ if condition == "Rain" }}
        <div class="promo">Don't forget your umbrella! ☔</div>
    {{ /if }}
{{ /kai:external }}
```

### Returning Visitor Welcome

```antlers
{{ kai:visitor }}
    {{ if is_returning }}
        <h1>Welcome back!</h1>
        <p>This is visit #{{ visit_count }}</p>
    {{ else }}
        <h1>Welcome!</h1>
        <p>First time here?</p>
    {{ /if }}
{{ /kai:visitor }}
```

### Location-Based Content

```antlers
{{ kai:condition attribute="country" operator="equals" value="NL" }}
    <p>Welkom! Dit is Nederlandse content.</p>
{{ /kai:condition }}

{{ kai:condition attribute="country" operator="not_equals" value="NL" }}
    <p>Welcome! This is international content.</p>
{{ /kai:condition }}
```

### Device-Specific CTAs

```antlers
{{ kai:condition attribute="device_type" operator="equals" value="mobile" }}
    <a href="tel:+31201234567" class="cta">Call Us Now</a>
{{ /kai:condition }}

{{ kai:condition attribute="device_type" operator="equals" value="desktop" }}
    <a href="/contact" class="cta">Contact Us</a>
{{ /kai:condition }}
```

## Development

### Local Development Setup

When developing the addon locally, use Composer's path repository to symlink the addon directory. Changes reflect instantly, and you commit in the addon repo separately.

#### Complete Setup

1. **Add path repository to your project's `composer.json`:**

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/Users/remko/Sites/_plugins/kai-personalize"
        }
    ],
    "require": {
        "keyagency/kai-personalize": "dev-main"
    }
}
```

> **Note:** The path repository tells Composer "this package is right here on my disk." Using just `"dev-main"` without the path repository makes Composer look for the package on Packagist.

2. **Install the addon:**

```bash
composer update keyagency/kai-personalize
```

3. **Verify the symlink was created:**

```bash
ls -la vendor/keyagency/kai-personalize
# Should show: kai-personalize -> /Users/remko/Sites/_plugins/kai-personalize
```

4. **Develop normally** - Changes in the addon directory reflect instantly in your project. Commit in the addon repo separately.

This is the standard Statamic addon development workflow recommended by the Statamic team.

### Running Tests

```bash
composer test
```

### Code Style

```bash
composer format
```

## Support

For support, please contact:
- **Email**: info@keyagency.nl
- **Website**: https://keyagency.nl

## License

Proprietary - Copyright © Key Agency

## Credits

Developed by Key Agency with AI agent Kai.

## Changelog

### v1.3.1 (2025-02-05)
**Tracker Queue Improvements:**
- Added configurable queue threshold (default: 5 events, was hardcoded 10)
- Added configurable periodic send interval (default: 20 seconds, was hardcoded 30)
- Added localStorage persistence for queued events
  - Survives page refreshes and navigation
  - Automatically restores events on page load
  - Discards stale events older than configured age (default: 1 hour)
- Improved error handling for sendBeacon failures
- Added queue settings to configuration file
- Added environment variables for queue configuration

### v1.3.0 (2025-02-03)
**Statamic 6 Compatibility & Major Fixes:**
- Updated navigation registration to use Statamic 6 `$nav->tools()` pattern
- Replaced FontAwesome icon strings with custom SVG navigation icon
- Implemented AJAX-based data loading for Dashboard and Analytics pages
  - Resolved navigation menu rendering issues caused by complex view data
  - Added `/data` endpoints for JSON data delivery
  - Client-side rendering with loading states
- Disabled tab handling in Settings page (all sections now visible by default)
- Fixed UUID string conversion issues in JavaScript
- Updated PHP version requirement to ^8.2 (Statamic 6 requirement)
- Updated Statamic version requirement to ^6.0
- Fixed inline JavaScript handling (Statamic 6 doesn't render `@push('scripts')`)
- Updated children navigation to use closure-based pattern per Statamic 6 conventions

### v1.2.1 (2025-02-02)
**Improvements:**
- Converted inline tracking JavaScript to external file (`tracker.js`)
- Tracker now served via route at `/kai-personalize/tracker.js`
- Improved browser caching with 1-day cache header
- Simplified tracking tag output to just config + script reference
- No build step or asset publishing required

### v1.2.0 (2025-02-02)
**New Features:**
- ActiveCampaign email campaign integration
- Automatic visitor identification from tracking cookies
- CRM data sync (contact info, tags, lists, custom fields)
- `kai:test-activecampaign` command for testing AC integration
- Cookie-based email retrieval with multiple encoding support
- AC attributes available in `{{ kai:visitor }}` tag

### v1.1.2 (2025-02-02)
**Security Enhancements:**
- Added HMAC SHA-256 signature validation for tracking endpoints
- Added rate limiting middleware (60 requests/minute, 500/hour per IP)
- Added timestamp validation to prevent replay attacks (5 minute expiry)
- Added nonce caching for duplicate request detection
- Added referer/origin validation for cross-origin protection
- Added input sanitization (event type regex, HTML strip, key whitelist)
- Added max events per request limit (50, configurable)
- Added `{{ kai:tracking }}` tag for signature generation
- Respects proxy headers for rate limiting (CF-Connecting-IP, X-Forwarded-For)

### v1.1.1 (2025-02-02)
**Bug Fixes & Improvements:**
- Fixed badge styling in Blade templates (added custom CSS for Statamic CP compatibility)
- Fixed collection handle detection for Statamic Page objects (structured collections)
- Enhanced `TrackVisitor` middleware to properly detect collection from `Statamic\Structures\Page` entries
- Added `getCollectionHandle()` method to handle both Entry and Page types
- Published CSS assets for proper badge styling in Control Panel views

### v1.1.0 (2025-01-XX)
**New Features:**
- Analytics & Engagement Scoring system
- Per-page analytics with views, unique visitors, scroll depth, and reading time
- Engagement score (0-100) for each visitor based on visits, page views, reading time, and scroll depth
- Behavioral summary tracking (max scroll depth, reading time, clicks, total events)
- Visitor page history with pagination
- Top engaged visitors ranking on dashboard
- New `kai:track` tag for manual event tracking
- New `kai:behavior` tag for retrieving behavioral statistics
- New PageAnalyticsController for page-level statistics
- Updated visitor profiles with engagement metrics

### v1.0.0 (2024-XX-XX)
**Initial Release:**
- Visitor tracking and session management
- Browser detection via jenssegers/agent
- MaxMind GeoIP2 local geolocation
- External API integration (Weather, Geolocation, Custom)
- Rule-based personalization
- Segment management
- API Connections management
- Privacy features (IP encryption, DNT, GDPR compliance)
- Control Panel interface with Dashboard, Rules, Visitors, Segments, and Settings

## Roadmap

### ✅ Completed (v1.0)
- [x] Control Panel UI for Rules management
- [x] Control Panel UI for Visitors management
- [x] Control Panel UI for Segments management
- [x] Control Panel UI for API Connections management
- [x] Dashboard with real-time statistics
- [x] Settings management interface

### ✅ Completed (v1.1)
- [x] Analytics & Engagement Scoring
- [x] Page-level analytics with scroll depth and reading time
- [x] Visitor page history with pagination
- [x] Behavioral summary (max scroll, reading time, clicks, events)
- [x] Top engaged visitors ranking
- [x] Event tracking tags (kai:track, kai:behavior)

### Short Term (Next Release)
- [ ] Enhanced Dashboard with charts and graphs
- [ ] Export/Import functionality for rules and settings
- [ ] Segment-based condition support in Rules

### Medium Term
- [ ] A/B testing capabilities
- [ ] Rule templates and presets
- [ ] Visitor journey visualization
- [ ] Segment-based condition support in Rules

### Long Term
- [ ] Machine learning predictions
- [ ] GraphQL API support
- [ ] WebSocket connections for real-time data
- [ ] Content recommendation engine
- [ ] Multi-variant testing
- [ ] More API provider integrations
