<?php

namespace KeyAgency\KaiPersonalize;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use KeyAgency\KaiPersonalize\Edition;
use KeyAgency\KaiPersonalize\Http\Middleware\TrackVisitor;
use KeyAgency\KaiPersonalize\Tags\Kai;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    const VERSION = '1.2.1';

    protected $tags = [
        Kai::class,
    ];

    protected $commands = [
        Commands\CleanupVisitorData::class,
        Commands\TestApiConnection::class,
        Commands\RefreshApiCache::class,
        Commands\PruneApiLogs::class,
        Commands\DownloadMaxMindDatabases::class,
        Commands\TestMaxMind::class,
        Commands\TestActiveCampaign::class,
    ];

    protected $stylesheets = [
        __DIR__.'/../resources/css/kai-personalize.css',
    ];

    protected $middlewareGroups = [
        'web' => [
            TrackVisitor::class,
        ],
    ];

    protected $listen = [
        // Events can be registered here
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    public function register()
    {
        // Register config early so we can check enabled flag in boot()
        $this->mergeConfigFrom(
            __DIR__.'/../config/kai-personalize.php', 'kai-personalize'
        );
    }

    public function bootAddon()
    {
        // Early return if addon is disabled - prevents ALL addon functionality
        if (! $this->isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'kai-personalize');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kai-personalize');

        $this->publishes([
            __DIR__.'/../config/kai-personalize.php' => config_path('kai-personalize.php'),
        ], 'kai-personalize-config');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/kai-personalize/css'),
        ], 'kai-personalize-assets');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/kai-personalize'),
        ], 'kai-personalize-translations');

        $this->bootNavigation();
        $this->bootPermissions();
        $this->bootEventListeners();
    }

    protected function bootNavigation()
    {
        Nav::extend(function ($nav) {
            $nav->create(__('kai-personalize::messages.addon_name'))
                ->section('Tools')
                ->route('kai-personalize.index')
                ->icon(File::exists(__DIR__.'/../resources/svg/nav-icon.svg') ? File::get(__DIR__.'/../resources/svg/nav-icon.svg') : null)
                ->can('view kai-personalize dashboard')
                ->children(function () use ($nav) {
                    $items = [
                        $nav->item(__('kai-personalize::messages.dashboard.title'))
                            ->route('kai-personalize.index'),
                        $nav->item(__('kai-personalize::messages.rules.title'))
                            ->route('kai-personalize.rules.index'),
                        $nav->item(__('kai-personalize::messages.visitors.title'))
                            ->route('kai-personalize.visitors.index'),
                        $nav->item(__('kai-personalize::messages.api_connections.title'))
                            ->route('kai-personalize.api-connections.index'),
                        $nav->item(__('kai-personalize::messages.blacklists.title'))
                            ->route('kai-personalize.blacklists.index'),
                        $nav->item(__('kai-personalize::messages.settings.title'))
                            ->route('kai-personalize.settings'),
                    ];

                    // Pro-only features
                    if (Edition::isPro()) {
                        $items[] = $nav->item(__('kai-personalize::messages.analytics.title'))
                            ->route('kai-personalize.analytics.pages');
                        $items[] = $nav->item(__('kai-personalize::messages.segments.title'))
                            ->route('kai-personalize.segments.index');
                    }

                    return $items;
                });
        });
    }

    protected function bootPermissions()
    {
        Permission::group('kai-personalize', __('kai-personalize::messages.addon_name'), function () {
            Permission::register('view kai-personalize dashboard')
                ->label(__('kai-personalize::messages.permissions.view_dashboard'));

            Permission::register('manage kai-personalize rules')
                ->label(__('kai-personalize::messages.permissions.manage_rules'));

            Permission::register('view kai-personalize visitors')
                ->label(__('kai-personalize::messages.permissions.view_visitors'));

            Permission::register('manage kai-personalize api connections')
                ->label(__('kai-personalize::messages.permissions.manage_api_connections'));

            Permission::register('manage kai-personalize blacklists')
                ->label(__('kai-personalize::messages.permissions.manage_blacklists'));

            Permission::register('manage kai-personalize settings')
                ->label(__('kai-personalize::messages.permissions.manage_settings'));

            // Pro-only permissions
            if (Edition::isPro()) {
                Permission::register('manage kai-personalize segments')
                    ->label(__('kai-personalize::messages.permissions.manage_segments'));

                Permission::register('view kai-personalize analytics')
                    ->label(__('kai-personalize::messages.permissions.view_analytics'));
            }
        });
    }

    protected function bootEventListeners()
    {
        // Listen for visitor events
        Event::listen('kai-personalize.visitor.created', function ($visitor) {
            // Handle visitor creation
        });

        Event::listen('kai-personalize.session.started', function ($session) {
            // Handle session start
        });
    }

    private function isEnabled(): bool
    {
        return config('kai-personalize.enabled', false);
    }

    public static function version(): string
    {
        return self::VERSION;
    }
}
