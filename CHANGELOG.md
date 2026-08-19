# Changelog

All notable changes to this project will be documented in this file.

## 1.2.9 (2026-08-19)

- [fix] **Tracking endpoint returned 419 Page Expired** - the tracker POSTs to `/!/kai-personalize/track`, which runs in the `web` middleware group and therefore through CSRF verification, while neither `tracker.js` nor `sendBeacon` sends a token. **No tracking event has ever arrived on a site that did not work around this.** The route now exempts itself from `ValidateCsrfToken`, so the host application needs no setup at all
- [breaking] **The HMAC signature layer is gone** - `KAI_TRACKING_SECRET`, `tracking.signature_secret` and `tracking.signature_ttl` are no longer read, and `TrackingSignatureService` is removed. The layer was never finished on the client side: the controller demanded `signature`, `timestamp` and `nonce`, but no tracker version ever sent them, so **a filled `KAI_TRACKING_SECRET` silently rejected every event with a 403**. It was also a weaker reimplementation of CSRF - no session binding, a 300s TTL, and unusable from `sendBeacon` on page unload. Requests are guarded by the origin/referer check and the rate limits instead. **Remove `KAI_TRACKING_SECRET` from your `.env`** - it is a dead key
- [breaking] **`{{ kai:tracking }}` no longer returns signature data** - it now returns `url` and `enabled`. `{{ kai:tracking:signature }}` is removed
- [changed] **The README's CSRF instructions are obsolete** - earlier versions told you to add a `validateCsrfTokens(except: …)` rule to `bootstrap/app.php`. That rule can be removed. It never worked as written either: the documented pattern `kai-personalize/track` misses Statamic's action prefix, so the real path `!/kai-personalize/track` never matched it
- [changed] **Dropped the `version` field from `package.json`** - it had drifted to 1.2.1 and served no purpose (the package is private and never published), leaving `ServiceProvider::VERSION` and the git tag as the only version sources. `TRACKER_VERSION` in `tracker.js` stays at 1.2.5; the script itself is unchanged in this release

## 1.2.8 (2026-08-15)

- [fix] **Tracking crashed on empty UTM parameters** - `?utm_term=` (as Google Ads appends) produced `Column 'attribute_value' cannot be null`, which aborted the rest of the request's tracking: language, device attributes, geolocation and the page view were all silently lost. Empty and non-string values are now skipped
- [fix] **Attribute writes no longer accept empty values** - `Visitor::setVisitorAttribute()` rejects null, empty strings and empty arrays, and maps unknown attribute types to `external` so an out-of-enum type (such as `crm`) can no longer truncate the column
- [fix] **One failing collector no longer wipes the rest** - page views are recorded before attributes, and each collector (campaign, language, agent, geolocation, ActiveCampaign) is isolated so a failure in one is logged without losing the others
- [fix] **Duplicate `blacklist` config key** - the key was defined twice in `config/kai-personalize.php` and the second definition silently won, leaving the bot filter off. **The default of `blacklist.enabled` is now `true`** - set `KAI_BLACKLIST_ENABLED=false` to keep the old behaviour, and republish the config with `php artisan vendor:publish --tag=kai-personalize-config --force`
- [new] **`blacklist.skip_known_bots`** - skips visitors the user agent parser recognises as a bot, without relying on hand-maintained patterns. The SEO whitelist still takes precedence
- [changed] **Bot check runs before entry resolution** - blacklisted traffic no longer pays for the expensive Statamic entry lookup
- [changed] **Derived attributes are no longer stored** - `time_of_day`, `day_of_week` and `google_maps_link` are computed on read. The first two were already computed live by the tags, and were being written on every single page view
- [changed] **Blacklist patterns are cached** and `MaxMindService` / `BlacklistService` are singletons, removing repeated queries and three `.mmdb` reader instantiations per request
- [new] `{{ kai:visitor }}` now exposes `latitude`, `longitude`, `google_maps_link`, `time_of_day` and `day_of_week`

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
