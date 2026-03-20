# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Kai Personalize is a Statamic 6 addon for adaptive content delivery based on visitor attributes and behavior. It provides server-side visitor tracking, session management, external API integrations, and rule-based personalization with a complete Control Panel interface.

## Statamic 6 Compatibility Notes

This addon is fully compatible with Statamic 6 (Vue 3). Key implementation patterns:

### Navigation Registration
Uses Statamic 6's `$nav->tools()` method with SVG icons:
```php
Nav::extend(function ($nav) {
    $nav->tools('Kai Personalize')
        ->route('kai-personalize.index')
        ->icon(File::get(__DIR__.'/../resources/svg/nav-icon.svg'))
        ->children(function () use ($nav) {
            return [
                $nav->item(__('kai-personalize::messages.dashboard.title'))
                    ->route('kai-personalize.index'),
                // ... more children
            ];
        });
});
```

### AJAX Data Loading Pattern
Dashboard and Analytics pages use AJAX data loading to avoid JSON encoding issues with complex Eloquent relationships:
- Controller `index()` methods only pass title to view
- Separate `data()` endpoints return JSON stats/tables
- Views include inline JavaScript (no `@push('scripts')` in Statamic 6)
- Client-side rendering with loading states

Example pattern:
```php
// Controller
public function index()
{
    return view('kai-personalize::dashboard.index', [
        'title' => __('kai-personalize::messages.dashboard.title'),
    ]);
}

public function data(Request $request)
{
    return response()->json([
        'stats' => $this->getStatistics(),
        'recentVisitors' => $this->getRecentVisitors(),
        // ...
    ]);
}
```

Route: `Route::get('/data', [DashboardController::class, 'data'])->name('data');`

## Development Commands

```bash
# Run tests (from parent project root)
composer test

# Code formatting
composer format

# Test API connections
php artisan kai:test-api connection-name

# Clean up visitor data
php artisan kai:cleanup --days=30

# Refresh API cache
php artisan kai:refresh-cache --all
php artisan kai:refresh-cache connection-name

# Prune API logs
php artisan kai:prune-logs --days=30

# Download MaxMind GeoIP2 databases
php artisan kai:maxmind:download --license=YOUR_LICENSE_KEY
php artisan kai:maxmind:download --database=city

# Test MaxMind database lookup
php artisan kai:maxmind:test 95.97.1.234

# Test ActiveCampaign integration
php artisan kai:test-activecampaign
php artisan kai:test-activecampaign --email=test@example.com
```

## Architecture

### Namespace & Autoloading
- Namespace: `KeyAgency\KaiPersonalize`
- Source: `src/` directory
- PSR-4 autoloading via composer

### Key Components

**ServiceProvider** (`src/ServiceProvider.php`)
- Extends `Statamic\Providers\AddonServiceProvider`
- Registers: tags, middleware, commands, routes, navigation, permissions
- CP routes registered manually via `Statamic::booted()` callback
- Middleware: `TrackVisitor` added to `web` group

**Models** (`src/Models/`)
All tables prefixed with `kai_personalize_`:
- `Visitor` - Identified by fingerprint_hash, tracks visit counts and sessions
- `VisitorSession` - Individual browsing sessions with IP/user agent
- `VisitorAttribute` - Key-value store for custom visitor data (supports expiration)
- `Segment` - Visitor segments with criteria-based assignment (many-to-many with visitors)
- `Rule` - Personalization rules with conditions
- `ApiConnection` - External API configurations
- `ApiCache` - Cached API responses
- `ApiLog` / `Log` - Request and personalization logs

**Tags** (`src/Tags/`)
All tags use `kai` as the handle prefix:
- `KaiVisitor` - `{{ kai:visitor }}` - Current visitor data
- `KaiCondition` - `{{ kai:condition }}` - Conditional content display
- `KaiExternal` - `{{ kai:external }}` - External API data (weather, geolocation, custom)
- `KaiContent` - `{{ kai:content }}` - Rule-based content
- `KaiSession` - `{{ kai:session:get/set }}` - Session data management
- `KaiApi` - `{{ kai:api }}` - Direct API calls with caching
- `KaiSegment` - Segment-based conditions

**Services** (`src/Services/`)
- `FingerprintService` - Server-side fingerprint generation
- `ActiveCampaignService` - ActiveCampaign email campaign integration
  - Reads tracking cookies to identify email campaign visitors
  - Fetches contact data (tags, lists, custom fields)
  - Stores as `crm` type visitor attributes
- `Api/ApiManager` - Central API management, resolves providers to services
- `Api/BaseApiService` - Abstract base for API services
- `Api/WeatherApiService`, `GeolocationApiService`, `CustomApiService` - Provider implementations

**Controllers** (`src/Http/Controllers/`)
CP controllers for: Dashboard, Rules, Visitors, Segments, ApiConnections, Settings

**Middleware** (`src/Http/Middleware/`)
- `TrackVisitor` - Automatic visitor tracking on web requests
  - Respects DNT headers and cookie consent
  - Skips CP, API, auth, and AJAX routes
  - Collects UTM parameters, referrer, language

### Configuration

Config file: `config/kai-personalize.php`

Key settings:
- `enabled` - Master kill switch (env: `KAI_PERSONALIZE_ENABLED`)
- `features.*` - Toggle fingerprinting, IP tracking, geolocation, behavioral tracking, ActiveCampaign
- `privacy.*` - IP encryption, DNT respect, GDPR mode, anonymization periods
- `session.*` - Session key prefixes and Statamic sync
- `apis.*` - Built-in provider configs (weather, geolocation, news, exchange)
- `activecampaign.*` - ActiveCampaign API config (url, api_key, cookie_name, cache_ttl)
- `queue.*` - Client-side event queue settings (threshold, send_interval, persist, storage_key, max_event_age)
- `retention.*` - Data retention periods

### Routes

CP routes: `/cp/kai-personalize/*` (registered in `routes/cp.php`)
- Dashboard: `/`
- Settings: `/settings`
- CRUD: `/rules/*`, `/visitors/*`, `/segments/*`, `/api-connections/*`

### Views

Blade templates in `resources/views/`:
- `dashboard/` - Main dashboard
- `rules/`, `visitors/`, `segments/`, `api-connections/` - CRUD views
- `settings/` - Configuration page

### Translations

Located in `resources/lang/`:
- `en/messages.php` - English
- `nl/messages.php` - Dutch

Access via: `__('kai-personalize::messages.key')`

## Version Management

### Version Number Location
- **Primary Source:** `src/ServiceProvider.php` - `const VERSION = '1.1.0';`
- **Display:** Settings page shows version + edition (Free/Pro)
- **Git Tags:** Official releases use git tags (e.g., `v1.1.0`)

### When to Update Version
Update the VERSION constant when:
1. **New features** are added (minor version bump, e.g., 1.1 → 1.2)
2. **Breaking changes** are made (major version bump, e.g., 1.x → 2.0)
3. **Bug fixes** are released (patch version bump, e.g., 1.1.0 → 1.1.1)

### Version Commit Workflow
When making changes that require a version update:

```bash
# 1. Update VERSION constant in src/ServiceProvider.php
# 2. Update README.md changelog
# 3. Commit the changes
git add src/ServiceProvider.php README.md
git commit -m "Bump version to 1.2.0

- Add new feature X
- Fix bug Y"

# 4. Create git tag for release
git tag v1.2.0
git push origin main --tags
```

### Version Bump Guidelines
- **Patch (1.1.0 → 1.1.1):** Bug fixes, small improvements, no breaking changes
- **Minor (1.1.0 → 1.2.0):** New features, backward-compatible additions
- **Major (1.x → 2.0):** Breaking changes, requires migration or config changes

### Accessing Version Programmatically
```php
// Get current version
$version = \KeyAgency\KaiPersonalize\ServiceProvider::version();

// Display in CP (already done in Settings page)
$version = ServiceProvider::version();
```

## Common Patterns

### Tag Implementation
Tags extend `Statamic\Tags\Tags` with `$handle = 'kai'`. Methods become tag names:
```php
// {{ kai:visitor }}
public function visitor(): array { ... }
```

### Model Relationships
- Visitors have many: sessions, attributes, logs
- Visitors belong to many: segments (via pivot)
- ApiConnections have many: cache entries, logs

### API Service Pattern
1. `ApiManager` resolves connection to appropriate service
2. Service extends `BaseApiService`
3. `fetch()` method handles caching, rate limiting, logging

### ActiveCampaign Integration
The ActiveCampaign service automatically identifies email campaign visitors:

**How it works:**
1. User clicks link in ActiveCampaign email → arrives with tracking cookie (`vgo_ee`)
2. `TrackVisitor` middleware detects cookie and calls `ActiveCampaignService`
3. Service fetches contact data (tags, lists, custom fields)
4. Data stored as `crm` type visitor attributes
5. Available in templates via `{{ kai:visitor }}`

**Stored attributes:**
- `ac_contact_id`, `ac_email`, `ac_first_name`, `ac_last_name`, `ac_phone`
- `ac_tags` (JSON array of tag names)
- `ac_lists` (JSON object with list membership status)
- `ac_custom_fields` (JSON object with field values)

**Usage example:**
```antlers
{{ kai:visitor }}
    {{ if ac_tags contains 'VIP' }}
        <p>Welcome back, VIP member!</p>
    {{ /if }}
{{ /kai:visitor }}
```

### Tracker Queue System
The client-side tracker (`{{ kai:track }}` tag) uses intelligent queue management:

**Features:**
- Configurable threshold - auto-send when queue reaches size (default: 5)
- Configurable interval - periodic send at interval (default: 20 seconds)
- localStorage persistence - survives page refreshes
- Stale event handling - discards events older than max age (default: 1 hour)
- Graceful error handling - handles sendBeacon failures and localStorage quota errors

**Configuration (via `.env` or `config/kai-personalize.php`):**
```env
KAI_QUEUE_THRESHOLD=5              # Send after 5 events
KAI_QUEUE_SEND_INTERVAL=20000      # Send every 20 seconds
KAI_QUEUE_PERSIST=true             # Enable localStorage persistence
KAI_QUEUE_STORAGE_KEY=kai_tracker_queue
KAI_QUEUE_MAX_EVENT_AGE=3600000    # Discard events older than 1 hour
```

**How it works:**
1. Events are queued with timestamp
2. Each event is saved to localStorage (if persistence enabled)
3. Events are sent when threshold reached OR interval elapses
4. On page load, previous events are restored from localStorage
5. Stale events (older than maxEventAge) are discarded on restoration
