# Changelog

All notable changes to this project will be documented in this file.

The format follows standard changelog conventions.

## 1.2.4 - 2026-05-05

### Added
- **Blacklist settings to config** - Added `blacklist.enabled` and `blacklist.logging` configuration options
- **Settings page badges** - Added visual indicators for Blacklist and Blacklist Logging features

## 1.2.3 - 2026-05-05

### Fixed
- **PSR-4 autoloading** - Renamed `src/database/` to `src/Database/` for proper PSR-4 compliance

## 1.2.2 - 2026-05-05

### Fixed
- **BlacklistSeeder autoloading** - Moved from `database/seeders/` to `src/Database/Seeders/` for proper PSR-4 autoloading
- Added `php artisan kai:seed-blacklist` command for easy database seeding

## 1.2.1 - 2026-05-05

### Added
- **Config option for tracker.js minification** - `KAI_USE_MINIFIED_JS` env var to control minified vs regular tracker

### Changed
- Updated blacklist CP views to use Statamic form layout conventions
- Fixed BlacklistController to extend Statamic CpController

## 1.2.0 - 2026-05-04

### Added
- **Bot Blacklist Feature**
  - Database-driven blacklist management via Control Panel
  - Block by bot name (e.g., Semrush, Ahrefs) or user agent pattern
  - Whitelist for essential SEO bots (Googlebot, Bingbot, etc.)
  - Automatic logging of blocked requests with hit counts
  - Pre-seeded with common bots, monitoring tools, and AI scrapers
  - Configuration: `KAI_BLACKLIST_ENABLED=false` (default off for safety)

- **Tracker.js Minification**
  - Automated build system using Terser
  - File size reduction: 23KB → 8.7KB (~62% smaller)
  - Automatic serving of minified version when available
  - Build command: `composer run build-js` or `npm run build`

### Changed
- Updated README.md with Cloudflare configuration (TRUSTED_PROXIES)

## 1.1.2 - 2026-03-22

### Changed
- Small bug fixes and documentation updates
- Version updates for Statamic cache fix

## 1.1.1 - 2026-03-20

### Changed
- **Edition Rename**: "Free" edition renamed to "Lite" edition
  - Updated `Edition::isFree()` to `Edition::isLite()`
  - Updated composer.json editions array
  - Updated translations (en/nl) with "Lite tier" references
  - Updated documentation (CLAUDE.md, LICENSE, README)

### Added
- Separate CHANGELOG.md file (moved from README.md)

## 1.1.0 - 2026-03-20

### Added
- **Core Features**
  - Visitor tracking with fingerprint identification
  - Session management with browse history
  - Browser & device detection (mobile/desktop/tablet/bot)
  - GeoIP2 location detection (local database, no API calls)
  - Campaign parameter tracking (UTM)
  - Referrer-based personalization
  - Cookie consent support

- **Personalization Engine**
  - Rule-based content delivery with condition builder
  - Dynamic visitor segments with criteria-based assignment
  - Antlers tags: `{{ kai:visitor }}`, `{{ kai:condition }}`, `{{ kai:content }}`, `{{ kai:segment }}`
  - Session data management: `{{ kai:session:get }}`, `{{ kai:session:set }}`

- **Analytics & Engagement**
  - Page-level analytics (views, unique visitors, scroll depth, reading time)
  - Engagement scoring (0-100) based on visits, page views, reading time, scroll depth
  - Behavioral event tracking (scroll depth, clicks, reading time, custom events)
  - Top engaged visitors ranking
  - Visitor page history with pagination

- **External API Integration**
  - Built-in providers: Weather, Geolocation, News, Exchange rates
  - Custom API connections with flexible authentication
  - API caching with configurable TTL
  - Test connection functionality
  - Rate limiting and error handling

- **ActiveCampaign Integration**
  - Automatic email campaign visitor tracking
  - CRM data sync (contact info, tags, lists, custom fields)
  - Cookie-based email identification (multiple encoding formats)

- **Control Panel**
  - Dashboard with real-time statistics
  - Analytics pages with engagement metrics
  - Rules management (CRUD with condition builder)
  - Visitors management (profiles, sessions, page history)
  - Segments management (CRUD with refresh functionality)
  - API Connections management (CRUD with testing)
  - Settings page with configuration overview

- **Security & Privacy**
  - HMAC SHA-256 signature validation for tracking endpoints
  - Rate limiting (60/minute, 500/hour per IP)
  - Timestamp validation for replay attack prevention
  - IP encryption and DNT respect
  - GDPR compliance features
  - Data anonymization and retention controls

- **Developer Features**
  - Tracker queue with localStorage persistence
  - Configurable event threshold and send interval
  - Artisan commands for testing and maintenance
  - MaxMind database download automation
  - Statamic 6 compatible (Vue 3)

- **Localization**
  - Full English and Dutch support
