# Changelog

All notable changes to this project will be documented in this file.

## 1.2.7 (2026-06-08)

- [fix] **Git tags for marketplace releases** - Added v1.2.x tags with proper "v" prefix for Statamic marketplace compatibility

## 1.2.6 (2026-06-08)

- [fix] **Config deep merge** - ServiceProvider now uses `array_replace_recursive()` instead of `mergeConfigFrom()` so missing nested config keys are always filled with addon defaults

## 1.2.5 (2026-05-05)

- [fix] **Removed deprecated ScriptProcessorNode** - Removed audio fingerprinting to fix browser deprecation warning
- [fix] Fingerprinting now uses Canvas + WebGL only (more reliable, no warnings)
- [new] **Extended screen resolution data** - Added devicePixelRatio, orientation, and available screen size to device capabilities tracking
- [new] **Server-side user agent tracking** - Full browser user agent string now captured server-side for reliability
- [new] **Tracker version in payload** - Each tracking request now includes tracker version for debugging
- [new] **Google Maps link** - Added google_maps_link attribute when latitude/longitude is available

## 1.2.4 (2026-05-05)

- [new] **Blacklist settings to config** - Added `blacklist.enabled` and `blacklist.logging` configuration options
- [new] **Settings page badges** - Added visual indicators for Blacklist and Blacklist Logging features

## 1.2.3 (2026-05-05)

- [fix] **PSR-4 autoloading** - Renamed `src/database/` to `src/Database/` for proper PSR-4 compliance

## 1.2.2 (2026-05-05)

- [fix] **BlacklistSeeder autoloading** - Moved from `database/seeders/` to `src/Database/Seeders/` for proper PSR-4 autoloading
- [new] Added `php artisan kai:seed-blacklist` command for easy database seeding

## 1.2.1 (2026-05-05)

- [new] **Config option for tracker.js minification** - `KAI_USE_MINIFIED_JS` env var to control minified vs regular tracker
- [changed] Updated blacklist CP views to use Statamic form layout conventions
- [fix] Fixed BlacklistController to extend Statamic CpController

## 1.2.0 (2026-05-04)

- [new] **Bot Blacklist Feature**
  - Database-driven blacklist management via Control Panel
  - Block by bot name (e.g., Semrush, Ahrefs) or user agent pattern
  - Whitelist for essential SEO bots (Googlebot, Bingbot, etc.)
  - Automatic logging of blocked requests with hit counts
  - Pre-seeded with common bots, monitoring tools, and AI scrapers
  - Configuration: `KAI_BLACKLIST_ENABLED=false` (default off for safety)

- [new] **Tracker.js Minification**
  - Automated build system using Terser
  - File size reduction: 23KB → 8.7KB (~62% smaller)
  - Automatic serving of minified version when available
  - Build command: `composer run build-js` or `npm run build`

- [changed] Updated README.md with Cloudflare configuration (TRUSTED_PROXIES)

## 1.1.2 (2026-03-22)

- [changed] Small bug fixes and documentation updates
- [changed] Version updates for Statamic cache fix

## 1.1.1 (2026-03-20)

- [changed] **Edition Rename**: "Free" edition renamed to "Lite" edition
  - Updated `Edition::isFree()` to `Edition::isLite()`
  - Updated composer.json editions array
  - Updated translations (en/nl) with "Lite tier" references
  - Updated documentation (CLAUDE.md, LICENSE, README)

- [new] Separate CHANGELOG.md file (moved from README.md)

## 1.1.0 (2026-03-20)

- [new] **Core Features**
  - Visitor tracking with fingerprint identification
  - Session management with browse history
  - Browser & device detection (mobile/desktop/tablet/bot)
  - GeoIP2 location detection (local database, no API calls)
  - Campaign parameter tracking (UTM)
  - Referrer-based personalization
  - Cookie consent support

- [new] **Personalization Engine**
  - Rule-based content delivery with condition builder
  - Dynamic visitor segments with criteria-based assignment
  - Antlers tags: `{{ kai:visitor }}`, `{{ kai:condition }}`, `{{ kai:content }}`, `{{ kai:segment }}`
  - Session data management: `{{ kai:session:get }}`, `{{ kai:session:set }}`

- [new] **Analytics & Engagement**
  - Page-level analytics (views, unique visitors, scroll depth, reading time)
  - Engagement scoring (0-100) based on visits, page views, reading time, scroll depth
  - Behavioral event tracking (scroll depth, clicks, reading time, custom events)
  - Top engaged visitors ranking
  - Visitor page history with pagination

- [new] **External API Integration**
  - Built-in providers: Weather, Geolocation, News, Exchange rates
  - Custom API connections with flexible authentication
  - API caching with configurable TTL
  - Test connection functionality
  - Rate limiting and error handling

- [new] **ActiveCampaign Integration**
  - Automatic email campaign visitor tracking
  - CRM data sync (contact info, tags, lists, custom fields)
  - Cookie-based email identification (multiple encoding formats)

- [new] **Control Panel**
  - Dashboard with real-time statistics
  - Analytics pages with engagement metrics
  - Rules management (CRUD with condition builder)
  - Visitors management (profiles, sessions, page history)
  - Segments management (CRUD with refresh functionality)
  - API Connections management (CRUD with testing)
  - Settings page with configuration overview

- [new] **Security & Privacy**
  - HMAC SHA-256 signature validation for tracking endpoints
  - Rate limiting (60/minute, 500/hour per IP)
  - Timestamp validation for replay attack prevention
  - IP encryption and DNT respect
  - GDPR compliance features
  - Data anonymization and retention controls

- [new] **Developer Features**
  - Tracker queue with localStorage persistence
  - Configurable event threshold and send interval
  - Artisan commands for testing and maintenance
  - MaxMind database download automation
  - Statamic 6 compatible (Vue 3)

- [new] **Localization**
  - Full English and Dutch support
