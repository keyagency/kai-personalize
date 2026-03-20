# Pro Licensing & Feature Tiers - Implementation Roadmap

## Overview

This document outlines the planned implementation of Pro licensing for Kai Personalize using Statamic's unified licensing system (The Outpost).

**Status:** 📋 Planned - Not Yet Implemented
**Estimated Effort:** 6-10 hours
**Priority:** Medium (post-v1.3.1)

---

## Statamic Licensing System

### Key Concepts

- **Unified licensing**: Single Statamic license key applies to entire installation
- **The Outpost**: Statamic's automatic license validation service
- **No manual validation**: Addon developers don't implement license checking
- **Local development**: Works without license (trial mode on localhost)
- **Editions system**: Can offer multiple tiers (free, pro, enterprise)

### How It Works

1. License validation occurs automatically when logged into Control Panel
2. Statamic handles validation through The Outpost service
3. Developers check edition from config, not validate licenses
4. Focus on feature gating based on edition

---

## Recommended Tier Structure

### FREE Tier

**Purpose:** Essential personalization for small sites

**Features:**
- Basic visitor tracking (server-side fingerprinting)
- Session management
- **5 active personalization rules max**
- Simple conditions only (equals, contains, not_equals)
- MaxMind geolocation (local database)
- **2 API connections max**
- **30-day data retention**
- Basic dashboard

**Limits:**
- 1,000 monthly tracked visitors (planned)
- 5 active rules
- 2 API connections
- No export, no advanced analytics
- No segments
- No behavioral tracking

### PRO Tier (Planned)

**Purpose:** Advanced personalization for growing businesses

**Additional Features:**
- Unlimited visitors and rules
- **ALL condition operators** (in, not_in, greater_than, less_than)
- **Complex nested conditions** with AND/OR
- Unlimited API connections + custom providers
- **365-day data retention**
- **Advanced analytics** & engagement scoring
- **Behavioral tracking** (scroll, clicks, reading time)
- **Data export functionality**
- **Segments** (unlimited)
- **ActiveCampaign integration**
- Enhanced CP features (search, filtering, bulk operations)

---

## Implementation Plan

### Phase 1: Core Edition System (1-2 hours)

**Files to Create:**

1. **`config/statamic/editions.php`** - Edition configuration
   ```php
   'edition' => env('KAI_PERSONALIZE_EDITION', null),
   'features' => [
       'free' => [...],
       'pro' => [...],
   ],
   ```

2. **`src/Edition.php`** - Edition helper class
   ```php
   class Edition
   {
       public static function get(): string;        // 'free' or 'pro'
       public static function isPro(): bool;
       public static function isFree(): bool;
       public static function hasFeature(string $feature): bool;
       public static function getLimit(string $limit): ?int;
   }
   ```

3. **`src/Http/Middleware/CheckEdition.php`** - Feature gate middleware
   ```php
   public function handle($request, Closure $next, string $feature)
   {
       if (!Edition::hasFeature($feature)) {
           return abort(403, 'This feature requires Pro edition');
       }
       return $next($request);
   }
   ```

4. **Update `config/kai-personalize.php`** - Add tiers configuration
   ```php
   'tiers' => [
       'free' => [
           'visitor_limit' => 1000,
           'retention_days' => 30,
           'max_rules' => 5,
           'max_api_connections' => 2,
           'max_segments' => 0,
           'analytics_enabled' => false,
           'behavioral_tracking' => false,
           'export_enabled' => false,
           'activecampaign_enabled' => false,
           'advanced_conditions' => false,
           'custom_api_providers' => false,
       ],
       'pro' => [
           'visitor_limit' => null,
           'retention_days' => 365,
           'max_rules' => null,
           'max_api_connections' => null,
           'max_segments' => null,
           'analytics_enabled' => true,
           'behavioral_tracking' => true,
           'export_enabled' => true,
           'activecampaign_enabled' => true,
           'advanced_conditions' => true,
           'custom_api_providers' => true,
       ],
   ],
   ```

### Phase 2: Feature Gating (2-3 hours)

**Controllers to Modify:**

1. **`RulesController.php`**
   - Apply rule count limit (max 5 in free tier)
   - Block advanced condition operators in free tier
   - Add upgrade prompts when limits reached

2. **`SegmentsController.php`**
   - Pro feature gate (entire controller blocked in free tier)
   - Hide from navigation in free tier

3. **`PageAnalyticsController.php`**
   - Pro feature gate (entire controller blocked in free tier)
   - Hide from navigation in free tier

4. **`ApiConnectionsController.php`**
   - Apply connection limit (max 2 in free tier)
   - Hide custom provider option in free tier

5. **`ExportController.php`** (NEW)
   - Pro feature gate (entire controller)
   - CSV/JSON export functionality

### Phase 3: Database Changes (30 minutes)

**Migration to Create:**

```php
// database/migrations/xxxx_create_kai_personalize_usage_table.php
Schema::create('kai_personalize_usage', function (Blueprint $table) {
    $table->id();
    $table->string('metric'); // 'monthly_visitors', etc.
    $table->integer('count')->default(0);
    $table->date('period_start');
    $table->date('period_end');
    $table->timestamps();
});
```

### Phase 4: UI Updates (2-3 hours)

**Navigation Updates:**
- Hide "Segments" and "Analytics" in free tier
- Show "Pro" badge on Pro-only features
- Add upgrade prompts when limits reached

**Upgrade Banner Example:**
```blade
@if(Edition::isFree() && $currentRules >= $maxRules)
<div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
    <p><strong>Free tier limit reached.</strong> Upgrade to Pro for unlimited rules.</p>
    <a href="https://statamic.com/marketplace/addons/kai-personalize" class="btn">
        Upgrade to Pro
    </a>
</div>
@endif
```

**Environment Configuration:**
```env
# .env - Choose edition
KAI_PERSONALIZE_EDITION=free  # or 'pro'
```

### Phase 5: ServiceProvider Updates (30 minutes)

**`src/ServiceProvider.php` changes:**
- Register CheckEdition middleware
- Update navigation to hide Pro items in free tier
- Register Edition helper

```php
protected $middlewareGroups = [
    'web' => [
        TrackVisitor::class,
    ],
];

protected $routeMiddleware = [
    'edition' => CheckEdition::class,
];

protected function bootNavigation()
{
    Nav::extend(function ($nav) {
        $nav->create(__('kai-personalize::messages.addon_name'))
            ->section('Tools')
            ->route('kai-personalize.index')
            ->children(function () use ($nav) {
                $items = [
                    $nav->item(__('Dashboard'))->route('kai-personalize.index'),
                    $nav->item(__('Rules'))->route('kai-personalize.rules.index'),
                    $nav->item(__('Visitors'))->route('kai-personalize.visitors.index'),
                    $nav->item(__('API Connections'))->route('kai-personalize.api-connections.index'),
                    $nav->item(__('Settings'))->route('kai-personalize.settings'),
                ];

                // Only show in Pro tier
                if (Edition::isPro()) {
                    $items[] = $nav->item(__('Analytics'))->route('kai-personalize.analytics.pages');
                    $items[] = $nav->item(__('Segments'))->route('kai-personalize.segments.index');
                }

                return $items;
            });
    });
}
```

### Phase 6: Testing (1-2 hours)

**Test Coverage:**
1. Free tier restrictions enforced
2. Pro tier features accessible
3. Navigation reflects edition
4. Upgrade prompts display correctly
5. Limits are enforced (rules, connections, etc.)
6. Advanced conditions blocked in free tier
7. Export functionality works in Pro only

---

## Feature Gates by Controller

| Controller | Free Tier | Pro Tier |
|------------|-----------|----------|
| Dashboard | ✅ Full | ✅ Full |
| RulesController | Max 5 rules, simple operators | Unlimited, all operators |
| VisitorsController | ✅ Full | ✅ Full |
| SegmentsController | ❌ Blocked | ✅ Full |
| AnalyticsController | ❌ Blocked | ✅ Full |
| ApiConnectionsController | Max 2 connections, built-in providers | Unlimited, custom providers |
| ExportController | ❌ Blocked | ✅ Full |
| SettingsController | ✅ Full | ✅ Full |

---

## Environment Variables

```env
# Edition override (for testing)
KAI_PERSONALIZE_EDITION=free  # or 'pro', or null for auto-detect
```

---

## Marketplace Distribution

1. List on https://statamic.com/marketplace/addons/
2. Set pricing for each edition (Free: $0, Pro: $XX)
3. Statamic handles license delivery and validation
4. Users see upgrade options in their Statamic account
5. No separate license keys needed (unified with Statamic license)

---

## Code Examples

### Edition Helper Usage

```php
use KeyAgency\KaiPersonalize\Edition;

// Check edition
if (Edition::isPro()) {
    // Enable Pro features
}

// Check specific feature
if (Edition::hasFeature('analytics')) {
    // Show analytics
}

// Get limits
$maxRules = Edition::getLimit('max_rules'); // Returns 5 for free, null for pro
```

### Middleware Usage

```php
// In routes/cp.php
Route::get('/analytics', [PageAnalyticsController::class, 'index'])
    ->middleware('edition:analytics')
    ->name('analytics.pages');
```

### Controller Usage

```php
public function store(Request $request)
{
    // Check rule limit in free tier
    if (Edition::isFree()) {
        $ruleCount = Rule::where('is_active', true)->count();
        if ($ruleCount >= Edition::getLimit('max_rules')) {
            return back()->with('error', 'Free tier limit reached. Upgrade to Pro for unlimited rules.');
        }
    }

    // Check for advanced operators
    $conditions = json_decode($request->conditions, true);
    if (Edition::isFree() && $this->hasAdvancedOperators($conditions)) {
        return back()->with('error', 'Advanced conditions require Pro edition.');
    }

    // Continue with rule creation...
}
```

---

## Migration Path for Existing Users

When this feature is implemented:

1. **Existing installations** default to Free tier
2. **No breaking changes** - all current features remain available
3. **Pro features** added as new capabilities
4. **Upgrade path** via Statamic marketplace
5. **Graceful degradation** - users see upgrade prompts when hitting limits

---

## Status

📋 **Planned** - This document serves as a roadmap for future Pro feature development.

**Next Steps:**
1. Finalize tier structure and pricing
2. Create implementation task breakdown
3. Schedule development sprint
4. Coordinate with Statamic for marketplace listing

---

## Questions to Answer Before Implementation

1. **Pricing**: What should the Pro tier cost?
2. **Limits**: Should Free tier have monthly visitor limits?
3. **Trial**: Should we offer a Pro trial period?
4. **Migration**: How to handle existing Pro features (analytics, segments)?
5. **Marketplace**: Submit to Statamic marketplace before or after implementation?
