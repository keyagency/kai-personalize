@extends('statamic::layout')

@section('title', __('kai-personalize::messages.settings.title'))

@push('head')
<style>
    :root {
        --kai-tab-border: #e5e7eb;
        --kai-tab-color: #6b7280;
        --kai-tab-hover: #374151;
        --kai-stat-bg: #f9fafb;
        --kai-stat-number: #111827;
        --kai-stat-label: #6b7280;
        --kai-command-bg: #f9fafb;
        --kai-command-code: #059669;
        --kai-command-desc: #6b7280;
        --kai-section-title: #111827;
        --kai-stat-shadow: rgba(0, 0, 0, 0.1);
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --kai-tab-border: #374151;
            --kai-tab-color: #9ca3af;
            --kai-tab-hover: #e5e7eb;
            --kai-stat-bg: #1f2937;
            --kai-stat-number: #f9fafb;
            --kai-stat-label: #9ca3af;
            --kai-command-bg: #1f2937;
            --kai-command-code: #34d399;
            --kai-command-desc: #9ca3af;
            --kai-section-title: #f9fafb;
            --kai-stat-shadow: rgba(0, 0, 0, 0.3);
        }
    }

    .kai-tabs {
        display: flex;
        border-bottom: 1px solid var(--kai-tab-border);
        margin-bottom: 1.5rem;
    }

    .kai-tab {
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        color: var(--kai-tab-color);
        font-weight: 500;
        transition: all 0.2s;
    }

    .kai-tab:hover {
        color: var(--kai-tab-hover);
    }

    .kai-tab.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }

    .kai-tab-content {
        display: none;
    }

    .kai-tab-content.active {
        display: block;
    }

    .kai-stat-card {
        text-align: center;
        padding: 1.5rem;
        background: var(--kai-stat-bg);
        border-radius: 0.5rem;
        transition: transform 0.2s, box-shadow 0.2s;
        flex: 1;
    }

    .kai-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px var(--kai-stat-shadow);
    }

    .kai-stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--kai-stat-number);
    }

    .kai-stat-label {
        font-size: 0.875rem;
        color: var(--kai-stat-label);
        margin-top: 0.25rem;
    }

    .kai-command-item {
        padding: 0.75rem;
        background: var(--kai-command-bg);
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
    }

    .kai-command-code {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 0.875rem;
        color: var(--kai-command-code);
    }

    .kai-command-desc {
        font-size: 0.75rem;
        color: var(--kai-command-desc);
        margin-top: 0.25rem;
    }

    .kai-section-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--kai-section-title);
    }

    .kai-command-item pre.kai-command-code {
        margin: 0;
        white-space: pre;
        overflow-x: auto;
    }
</style>
@endpush

@section('content')
<div class="kai-personalize">
    <div>
        <div class="flex items-center justify-between mb-3">
            <h1 class="flex-1">{{ __('kai-personalize::messages.settings.title') }}</h1>
        </div>

        <div class="card p-0 overflow-hidden">
            <!-- Tabs Header -->
            <div class="kai-tabs">
                <div class="kai-tab active" data-tab="features" onclick="kaiSwitchTab('features')">
                    Feature Status
                </div>
                <div class="kai-tab" data-tab="database" onclick="kaiSwitchTab('database')">
                    Database Statistics
                </div>
                <div class="kai-tab" data-tab="commands" onclick="kaiSwitchTab('commands')">
                    Artisan Commands
                </div>
                <div class="kai-tab" data-tab="antlers" onclick="kaiSwitchTab('antlers')">
                    Antlers Samples
                </div>
            </div>

            <!-- Tab Content: Features -->
            <div class="kai-tab-content active p-6" data-tab-content="features">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold mb-1">{{ __('kai-personalize::messages.addon_name') }}</h2>
                        <p class="text-gray-600">{{ __('kai-personalize::messages.addon_description') }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">v{{ $version }}</div>
                        <div class="text-sm text-gray-500">{{ ucfirst($edition) }} Edition</div>
                    </div>
                </div>

                <h3 class="kai-section-title">Core Features</h3>
                <div class="mb-6">
                    <span class="badge {{ $config['enabled'] ? 'badge-success' : 'badge-danger' }}">Addon {{ $config['enabled'] ? 'Enabled' : 'Disabled' }}</span>
                    <span class="badge {{ $config['features']['fingerprinting'] ? 'badge-success' : 'badge-neutral' }}">Fingerprinting</span>
                    <span class="badge {{ $config['features']['ip_tracking'] ? 'badge-success' : 'badge-neutral' }}">IP Tracking</span>
                    <span class="badge {{ $config['features']['geolocation'] ? 'badge-success' : 'badge-neutral' }}">Geolocation</span>
                    <span class="badge {{ $config['features']['behavioral_tracking'] ? 'badge-success' : 'badge-neutral' }}">Behavioral Tracking</span>
                    <span class="badge {{ $config['features']['page_view_tracking'] ? 'badge-success' : 'badge-neutral' }}">Page View Tracking</span>
                    <span class="badge {{ $config['features']['external_data'] ? 'badge-success' : 'badge-neutral' }}">External Data</span>
                    <span class="badge {{ $config['features']['activecampaign'] ? 'badge-success' : 'badge-neutral' }}">ActiveCampaign</span>
                </div>

                <h3 class="kai-section-title">JavaScript Tracking Features</h3>
                <div class="mb-6">
                    <span class="badge {{ $config['features']['scroll_tracking'] ? 'badge-success' : 'badge-neutral' }}">Scroll Tracking</span>
                    <span class="badge {{ $config['features']['click_tracking'] ? 'badge-success' : 'badge-neutral' }}">Click Tracking</span>
                    <span class="badge {{ $config['features']['form_tracking'] ? 'badge-success' : 'badge-neutral' }}">Form Tracking</span>
                    <span class="badge {{ $config['features']['video_tracking'] ? 'badge-success' : 'badge-neutral' }}">Video Tracking</span>
                </div>

                <h3 class="kai-section-title">Privacy Settings</h3>
                <div class="mb-6">
                    <span class="badge {{ $config['privacy']['encrypt_ip'] ? 'badge-success' : 'badge-neutral' }}">IP Encryption</span>
                    <span class="badge badge-info">Anonymize after {{ $config['privacy']['anonymize_after_days'] }} days</span>
                    <span class="badge {{ $config['privacy']['respect_dnt'] ? 'badge-success' : 'badge-neutral' }}">Do Not Track</span>
                </div>

                <h3 class="kai-section-title">ActiveCampaign Configuration</h3>
                <div>
                    <span class="badge badge-info">URL: {{ $config['activecampaign']['api_url'] ?? 'Not configured' }}</span>
                    <span class="badge {{ !empty($config['activecampaign']['api_key']) ? 'badge-success' : 'badge-neutral' }}">API Key: {{ !empty($config['activecampaign']['api_key']) ? '••••••••' : 'Not set' }}</span>
                    <span class="badge badge-info">Cookie: {{ $config['activecampaign']['cookie_name'] ?? 'vgo_ee' }}</span>
                    <span class="badge badge-info">Cache: {{ $config['activecampaign']['cache_ttl'] ?? 1440 }} min</span>
                </div>

                <div class="border-t pt-4 mt-6">
                    <div class="text-sm text-gray-600">
                        <strong>Configuration Location:</strong> <code class="bg-gray-100 px-1 rounded">config/kai-personalize.php</code><br>
                        <strong>Environment Variables:</strong> See <code class="bg-gray-100 px-1 rounded">.env</code> file for feature toggles
                    </div>
                </div>
            </div>

            <!-- Tab Content: Database Statistics -->
            <div class="kai-tab-content p-6" data-tab-content="database">
                <h2 class="text-lg font-bold mb-2">Database Statistics</h2>
                <p class="text-gray-600 mb-6">Real-time counts of records stored by Kai Personalize.</p>

                <h3 class="kai-section-title">Tracking</h3>
                <div style="display:flex;gap:1rem;margin-bottom:2rem">
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\Visitor::count()) }}</div>
                        <div class="kai-stat-label">Visitors</div>
                    </div>
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\VisitorSession::count()) }}</div>
                        <div class="kai-stat-label">Sessions</div>
                    </div>
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\PageView::count()) }}</div>
                        <div class="kai-stat-label">Page Views</div>
                    </div>
                </div>

                <h3 class="kai-section-title">Personalization</h3>
                <div style="display:flex;gap:1rem;margin-bottom:2rem">
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\Rule::count()) }}</div>
                        <div class="kai-stat-label">Rules</div>
                    </div>
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\Segment::count()) }}</div>
                        <div class="kai-stat-label">Segments</div>
                    </div>
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\Event::count()) }}</div>
                        <div class="kai-stat-label">Events</div>
                    </div>
                </div>

                <h3 class="kai-section-title">API & Logging</h3>
                <div style="display:flex;gap:1rem;margin-bottom:2rem">
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\ApiConnection::count()) }}</div>
                        <div class="kai-stat-label">API Connections</div>
                    </div>
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\ApiLog::count()) }}</div>
                        <div class="kai-stat-label">API Logs</div>
                    </div>
                    <div class="kai-stat-card">
                        <div class="kai-stat-number">{{ number_format(\KeyAgency\KaiPersonalize\Models\Log::count()) }}</div>
                        <div class="kai-stat-label">Activity Logs</div>
                    </div>
                </div>

                <h3 class="kai-section-title">Data Retention Summary</h3>
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Visitor Data:</span>
                            <span class="font-medium ml-2">{{ $config['retention']['visitor_data_days'] }} days</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Session Data:</span>
                            <span class="font-medium ml-2">{{ $config['retention']['session_data_days'] }} days</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Page View Data:</span>
                            <span class="font-medium ml-2">{{ $config['retention']['page_view_data_days'] }} days</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Event Data:</span>
                            <span class="font-medium ml-2">{{ $config['retention']['event_data_days'] }} days</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Artisan Commands -->
            <div class="kai-tab-content p-6" data-tab-content="commands">
                <h2 class="text-lg font-bold mb-2">Available Artisan Commands</h2>
                <p class="text-gray-600 mb-6">Command-line tools for managing Kai Personalize data and testing integrations.</p>

                <h3 class="kai-section-title">Data Management</h3>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:cleanup</div>
                    <div class="kai-command-desc">Clean old visitor data based on retention settings</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:cleanup --days=30</div>
                    <div class="kai-command-desc">Clean visitor data older than 30 days</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:cleanup --all</div>
                    <div class="kai-command-desc">Delete ALL visitor tracking data without date restriction (requires confirmation)</div>
                </div>

                <h3 class="kai-section-title mt-6">API Connections</h3>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:test-api [connection-name]</div>
                    <div class="kai-command-desc">Test an API connection by name</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:refresh-cache --all</div>
                    <div class="kai-command-desc">Clear all cached API responses</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:refresh-cache [connection-name]</div>
                    <div class="kai-command-desc">Clear cache for specific connection</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:prune-logs --days=30</div>
                    <div class="kai-command-desc">Remove API logs older than specified days</div>
                </div>

                <h3 class="kai-section-title mt-6">MaxMind GeoIP2</h3>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:maxmind:download --license=YOUR_KEY</div>
                    <div class="kai-command-desc">Download MaxMind GeoLite2 databases</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:maxmind:download --database=city</div>
                    <div class="kai-command-desc">Download only city database</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:maxmind:test [ip-address]</div>
                    <div class="kai-command-desc">Test MaxMind database lookup</div>
                </div>

                <h3 class="kai-section-title mt-6">ActiveCampaign</h3>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:test-activecampaign</div>
                    <div class="kai-command-desc">Test ActiveCampaign API connection</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:test-activecampaign --email=user@example.com</div>
                    <div class="kai-command-desc">Test contact lookup by email</div>
                </div>
                <div class="kai-command-item">
                    <div class="kai-command-code">php artisan kai:test-activecampaign --test-cookie</div>
                    <div class="kai-command-desc">Test cookie-based retrieval (interactive)</div>
                </div>

                <div class="border-t pt-4 mt-6">
                    <p class="text-sm text-gray-600">
                        Run <code class="bg-gray-100 px-1 rounded">php artisan list | grep kai</code> to see all available Kai Personalize commands.
                    </p>
                </div>
            </div>

            <!-- Tab Content: Antlers Samples -->
            <div class="kai-tab-content p-6" data-tab-content="antlers" v-pre>
                <h2 class="text-lg font-bold mb-2">Antlers Template Examples</h2>
                <p class="text-gray-600 mb-6">Copy-paste code samples for using Kai Personalize tags in your Antlers templates.</p>

                @verbatim
                <h3 class="kai-section-title">Layout Integration</h3>
                <p class="text-sm text-gray-600 mb-2">Add the tracker to your layout file (e.g. <code class="bg-gray-100 px-1 rounded">layout.antlers.html</code>) before the closing <code class="bg-gray-100 px-1 rounded">&lt;/body&gt;</code> tag:</p>
                <div class="kai-command-item">
                    <div class="kai-command-code">{{ kai:track }}</div>
                    <div class="kai-command-desc">Outputs the behavioral tracking JavaScript (scroll, click, fingerprinting, etc.)</div>
                </div>

                <h3 class="kai-section-title mt-6">Display Visitor Data</h3>
                <p class="text-sm text-gray-600 mb-2">Access all tracked visitor attributes in a tag pair:</p>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ kai:visitor }}
  &lt;p&gt;Welcome{{ if is_returning }} back{{ /if }}!&lt;/p&gt;
  &lt;p&gt;Visit count: {{ visit_count }}&lt;/p&gt;
  &lt;p&gt;Browser: {{ browser }} ({{ device_type }})&lt;/p&gt;
  &lt;p&gt;Location: {{ city }}, {{ country }}&lt;/p&gt;
{{ /kai:visitor }}</pre>
                    <div class="kai-command-desc">Available fields: fingerprint, visit_count, is_returning, country, city, region, browser, platform, device_type, is_mobile, is_desktop, utm_source, utm_medium, utm_campaign, language, timezone</div>
                </div>

                <h3 class="kai-section-title mt-6">Conditional Content</h3>
                <p class="text-sm text-gray-600 mb-2">Show or hide content based on visitor attributes:</p>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ if {kai:condition attribute="country" operator="equals" value="NL"} }}
  &lt;p&gt;Welkom bij onze Nederlandse website!&lt;/p&gt;
{{ /if }}

{{ if {kai:condition attribute="visit_count" operator=">=" value="3"} }}
  &lt;p&gt;Thanks for being a loyal visitor!&lt;/p&gt;
{{ /if }}

{{ if {kai:condition attribute="device_type" operator="equals" value="mobile"} }}
  &lt;p&gt;Download our mobile app!&lt;/p&gt;
{{ /if }}</pre>
                    <div class="kai-command-desc">Operators: equals, not_equals, contains, not_contains, greater_than, less_than, greater_or_equal, less_or_equal, in, not_in, starts_with, ends_with</div>
                </div>

                <h3 class="kai-section-title mt-6">Behavioral Data</h3>
                <p class="text-sm text-gray-600 mb-2">Access scroll depth, click data, and engagement metrics:</p>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ kai:behavior }}
  &lt;p&gt;Scroll depth: {{ scroll_depth }}%&lt;/p&gt;
  &lt;p&gt;Page views: {{ page_views }}&lt;/p&gt;
  &lt;p&gt;Active time: {{ active_time_seconds }}s&lt;/p&gt;
  {{ if is_engaged }}&lt;p&gt;Engaged visitor!&lt;/p&gt;{{ /if }}
{{ /kai:behavior }}

{{# Behavioral conditionals #}}
{{ if {kai:if:scroll_depth operator=">=" value="75"} }}
  &lt;div&gt;You read most of this page — here's more!&lt;/div&gt;
{{ /if }}

{{ if {kai:if:visited url="/pricing"} }}
  &lt;p&gt;Still interested? Here's a special offer.&lt;/p&gt;
{{ /if }}

{{ if {kai:if:page_views operator=">=" value="3"} }}
  &lt;p&gt;You've explored several pages — need help?&lt;/p&gt;
{{ /if }}</pre>
                    <div class="kai-command-desc">Behavior fields: scroll_depth, total_clicks, rage_clicks, dead_clicks, active_time_seconds, page_views, is_engaged, has_scrolled_deep, visit_count</div>
                </div>

                <h3 class="kai-section-title mt-6">Recently Viewed Pages</h3>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ kai:page_views limit="5" }}
  &lt;a href="{{ url }}"&gt;{{ title }}&lt;/a&gt; — {{ viewed_human }}
{{ /kai:page_views }}

{{# Filter by collection #}}
{{ kai:page_views collection="articles" limit="3" }}
  &lt;a href="{{ url }}"&gt;{{ title }}&lt;/a&gt;
{{ /kai:page_views }}</pre>
                    <div class="kai-command-desc">Fields: url, title, slug, collection, viewed_at, viewed_human, is_entry</div>
                </div>

                <h3 class="kai-section-title mt-6">Session Data</h3>
                <div class="kai-command-item">
<pre class="kai-command-code">{{# Store a value in session #}}
{{ kai:session:set key="preferred_color" value="blue" }}

{{# Retrieve a session value #}}
{{ kai:session:get key="preferred_color" }}

{{# Check if visitor is tracked #}}
{{ if {kai:session:tracked} }}
  &lt;p&gt;Your visit is being tracked.&lt;/p&gt;
{{ /if }}

{{# Remove a session value #}}
{{ kai:session:forget key="preferred_color" }}</pre>
                </div>

                <h3 class="kai-section-title mt-6">Rule-Based Content</h3>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ kai:content rules="returning-nl-visitors" }}
  {{ if condition_met }}
    &lt;div&gt;Special content for rule: {{ rule_name }}&lt;/div&gt;
  {{ else }}
    &lt;div&gt;Default content for everyone&lt;/div&gt;
  {{ /if }}
{{ /kai:content }}</pre>
                    <div class="kai-command-desc">Rules are configured in the CP under Kai Personalize &rarr; Rules</div>
                </div>

                <h3 class="kai-section-title mt-6">External API Data</h3>
                <div class="kai-command-item">
<pre class="kai-command-code">{{# Weather data (auto-detects location) #}}
{{ kai:external source="weather" }}
  &lt;p&gt;{{ temperature }}°C — {{ description }}&lt;/p&gt;
{{ /kai:external }}

{{# Geolocation lookup #}}
{{ kai:external source="geolocation" }}
  &lt;p&gt;{{ country }}, {{ city }}&lt;/p&gt;
{{ /kai:external }}

{{# Custom API connection #}}
{{ kai:external source="custom" connection="my-api" endpoint="/products" }}
  {{ results }}
    &lt;p&gt;{{ name }}&lt;/p&gt;
  {{ /results }}
{{ /kai:external }}</pre>
                    <div class="kai-command-desc">API connections are configured in the CP under Kai Personalize &rarr; API Connections</div>
                </div>

                <h3 class="kai-section-title mt-6">Direct API Calls</h3>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ kai:api url="https://api.example.com/data" method="GET" cache="600" }}
  {{ results }}
    &lt;p&gt;{{ name }}&lt;/p&gt;
  {{ /results }}
{{ /kai:api }}</pre>
                    <div class="kai-command-desc">Parameters: url (required), method (GET/POST/PUT/DELETE), cache (seconds, default 300), params:key for query params</div>
                </div>

                <h3 class="kai-section-title mt-6">Segment Checks</h3>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ if {kai:segment name="returning-visitors"} }}
  &lt;p&gt;Content for returning visitors segment&lt;/p&gt;
{{ /if }}</pre>
                    <div class="kai-command-desc">Segments are defined in the CP under Kai Personalize &rarr; Segments</div>
                </div>

                <h3 class="kai-section-title mt-6">HMAC Tracking Signature</h3>
                <p class="text-sm text-gray-600 mb-2">For custom JavaScript tracking with HMAC validation:</p>
                <div class="kai-command-item">
<pre class="kai-command-code">{{ kai:tracking }}
  &lt;script&gt;
    window.KaiTracking = {
      signature: '{{ signature }}',
      nonce: '{{ nonce }}',
      timestamp: {{ timestamp }},
      enabled: {{ enabled }},
    };
  &lt;/script&gt;
{{ /kai:tracking }}</pre>
                    <div class="kai-command-desc">Use this when building custom tracking integrations that need HMAC signature validation</div>
                </div>

                <div class="border-t pt-4 mt-6">
                    <p class="text-sm text-gray-600">
                        All tags use the <code class="bg-gray-100 px-1 rounded">kai:</code> prefix. For the full tag reference, see the addon documentation.
                    </p>
                </div>
                @endverbatim
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<script>
    function kaiSwitchTab(tab) {
        document.querySelectorAll('.kai-tab').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.kai-tab-content').forEach(el => el.classList.remove('active'));
        document.querySelector('.kai-tab[data-tab="' + tab + '"]').classList.add('active');
        document.querySelector('.kai-tab-content[data-tab-content="' + tab + '"]').classList.add('active');
        history.replaceState(null, '', '#' + tab);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var hash = window.location.hash.replace('#', '');
        if (hash && document.querySelector('.kai-tab[data-tab="' + hash + '"]')) {
            kaiSwitchTab(hash);
        }

        // Add click handlers to tabs
        document.querySelectorAll('.kai-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                kaiSwitchTab(this.getAttribute('data-tab'));
            });
        });
    });
</script>
@endpush
