# Kai Personalize Addon - Statamic 6 Upgrade Fix Plan

## Problem Summary

The `kai-personalize` addon was working in Statamic 5 but has issues after upgrading to Statamic 6. The main issue is that the Control Panel navigation menu is not working properly.

## Root Causes Identified

### 1. Route Name Mismatch (Critical Issue)
**Location**: `src/ServiceProvider.php:114-142` (navigation) vs `$routes` property behavior

- Navigation references routes like: `kai-personalize.index`
- When using `$routes` property with `'cp'` key, Statamic automatically adds `statamic.cp.` prefix
- Result: Routes will be `statamic.cp.kai-personalize.*`, not `kai-personalize.*`

**Fix**: Update navigation to use `statamic.cp.kai-personalize.*` prefix for all route names.

### 2. ServiceProvider Not Following Statamic 6 Patterns
**Location**: `src/ServiceProvider.php`

**Issues**:
- Line 52: Using `boot()` with `parent::boot()` instead of `bootAddon()` (Statamic 6 pattern)
- Line 62: Manual middleware registration: `$this->app['router']->pushMiddlewareToGroup('web', TrackVisitor::class);`
- Should use `$middlewareGroups` property instead
- Lines 81-107: Routes registered manually in `Statamic::booted()` callback
- Should use `$routes` property instead (automatic registration and prefixing)

**Fix**: Use Statamic 6 properties (`$middlewareGroups`, `$routes`) and `bootAddon()` method.

### 3. Duplicate `Statamic::booted()` Wrapping
**Location**: `src/ServiceProvider.php:114-142`

- `bootNavigation()` wraps its logic in `Statamic::booted()` (line 117)
- When using `bootAddon()`, this is redundant since it's already called after Statamic is booted
- **Fix**: Remove the inner `Statamic::booted()` wrapper in `bootNavigation()`

### 4. isEnabled() Not Fully Disabling Addon
**Location**: `src/ServiceProvider.php:52-59, 179-183`

**Issues**:
a) `env()` only reads from `.env` file, not from config. After config is cached, this may not work as expected.
b) When disabled, routes are still registered (only middleware and other boot logic is skipped)
c) Navigation is still shown even when addon is disabled

**Fixes needed**:
a) Should check `config('kai-personalize.enabled')` instead of `env()`
b) **Routes must be conditionally registered** - they should only be registered when addon is enabled
c) **Navigation must be conditionally registered** - should only be shown when addon is enabled
d) Use early return pattern at top of `bootAddon()`

## Implementation Plan

### Step 1: Fix Route Name Mismatch
**File**: `src/ServiceProvider.php`

**Action**: Update navigation route names to match Statamic 6 `$routes` property behavior

Since `$routes` property automatically prefixes CP routes with `statamic.cp.`, update navigation:
- Change `'kai-personalize.index'` to `'statamic.cp.kai-personalize.index'`
- Change `'kai-personalize.analytics.pages'` to `'statamic.cp.kai-personalize.analytics.pages'`
- Change `'kai-personalize.rules.index'` to `'statamic.cp.kai-personalize.rules.index'`
- Change `'kai-personalize.visitors.index'` to `'statamic.cp.kai-personalize.visitors.index'`
- Change `'kai-personalize.segments.index'` to `'statamic.cp.kai-personalize.segments.index'`
- Change `'kai-personalize.api-connections.index'` to `'statamic.cp.kai-personalize.api-connections.index'`
- Change `'kai-personalize.settings'` to `'statamic.cp.kai-personalize.settings'`

### Step 2: Update ServiceProvider to Follow Statamic 6 Patterns
**File**: `src/ServiceProvider.php`

1. Replace `boot()` with `bootAddon()` (Statamic 6 pattern)
2. Add `$middlewareGroups` property at class level:
   ```php
   protected $middlewareGroups = [
       'web' => [
           TrackVisitor::class,
       ],
   ];
   ```
3. Add `$routes` property at class level:
   ```php
   protected $routes = [
       'cp' => __DIR__.'/../routes/cp.php',
   ];
   ```
   Note: This automatically registers routes with `statamic.cp.` prefix
4. Remove manual route registration from `bootAddon()` (lines 81-107) - handled by `$routes` property
5. Remove manual middleware registration from `bootAddon()` (line 62) - handled by `$middlewareGroups` property

### Step 3: Fix isEnabled() and Ensure Complete Disable
**File**: `src/ServiceProvider.php`

**3a. Fix isEnabled() Method**

Change from:
```php
private function isEnabled(): bool
{
    return env('KAI_ENABLED', false);
}
```

To:
```php
private function isEnabled(): bool
{
    return config('kai-personalize.enabled', false);
}
```

**3b. Use Early Return Pattern in bootAddon()**

Move the `isEnabled()` check to the VERY TOP of `bootAddon()` method, right after opening brace:

```php
public function bootAddon()
{
    // Early return if addon is disabled - prevents ALL addon functionality
    if (! $this->isEnabled()) {
        return;
    }

    // Rest of boot logic...
}
```

This ensures:
- Routes are NOT registered (handled by `$routes` property which checks `bootAddon()`)
- Middleware is NOT registered (handled by `$middlewareGroups` property)
- Navigation is NOT shown
- No addon resources are loaded

### Step 4: Clean Up Navigation Registration
**File**: `src/ServiceProvider.php:114-142`

Remove the duplicate `Statamic::booted()` wrapper in `bootNavigation()` since `bootAddon()` is already called after Statamic is booted.

## Files to Modify

| File | Changes |
|------|---------|
| `src/ServiceProvider.php` | 1) Update navigation route names to 'statamic.cp.kai-personalize.*', 2) Use bootAddon() instead of boot(), 3) Add $middlewareGroups property, 4) Add $routes property, 5) Add isEnabled() early return at top of bootAddon(), 6) Remove manual route/middleware registration, 7) Remove duplicate Statamic::booted() wrapping |
| `routes/cp.php` | No changes needed |

## Verification Steps

1. **Clear all caches**:
   ```bash
   php please cache:clear
   php please stache:clear
   composer dump-autoload
   ```

2. **Test Addon Enabled (KAI_ENABLED=true)**:
   - Set `KAI_ENABLED=true` in `.env`
   - Run: `php please cache:clear`
   - Login to `/cp`
   - **Verify "Kai Personalize" appears in Tools navigation**
   - **Verify all 7 sub-items appear and are clickable**:
     - Dashboard (`statamic.cp.kai-personalize.index`)
     - Analytics → Pages (`statamic.cp.kai-personalize.analytics.pages`)
     - Rules (`statamic.cp.kai-personalize.rules.index`)
     - Visitors (`statamic.cp.kai-personalize.visitors.index`)
     - Segments (`statamic.cp.kai-personalize.segments.index`)
     - API Connections (`statamic.cp.kai-personalize.api-connections.index`)
     - Settings (`statamic.cp.kai-personalize.settings`)
   - Click each menu item and verify:
     - Page loads without errors
     - Correct content is displayed
     - No 404 errors
   - Check routes: `php please route:list | grep kai-personalize`
     - Verify routes are prefixed with `statamic.cp.kai-personalize.`
   - Visit frontend pages and verify `TrackVisitor` middleware is working
   - Check logs for any errors

3. **Test Addon Fully Disabled (KAI_ENABLED=false)**:
   - Set `KAI_ENABLED=false` in `.env`
   - Run: `php please cache:clear`
   - Login to `/cp`
   - **Verify "Kai Personalize" does NOT appear in navigation**
   - Check routes: `php please route:list | grep kai-personalize`
     - **Verify NO kai-personalize routes are registered**
   - Visit frontend pages and verify tracking does NOT occur
   - Check that no kai-personalize middleware is running

4. **Post-Implementation Review**:
   - Review all changes made to `src/ServiceProvider.php`
   - Verify menu and sub-items work correctly in the browser
   - Test all navigation links from the Kai Personalize menu
   - Verify no JavaScript errors in browser console
   - Check that the addon icon displays correctly

## References

- Statamic 6 Addon Docs: https://statamic.dev/addons/building-an-addon
- Statamic 6 Service Provider Pattern: Use `AddonServiceProvider` with `bootAddon()`
- Middleware registration via `$middlewareGroups` property
- Route registration via `$routes` property with automatic prefixing
