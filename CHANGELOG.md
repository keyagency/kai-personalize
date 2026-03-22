# Changelog

All notable changes to this project will be documented in this file.

The format follows standard changelog conventions.

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
