@extends('statamic::layout')

@section('title', __('kai-personalize::messages.dashboard.title'))

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ __('kai-personalize::messages.dashboard.title') }}</h1>
    </div>

    <div id="dashboard-loading" class="text-center p-8">
        <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
        <p class="text-gray-600">Loading dashboard data...</p>
    </div>

    <div id="dashboard-content" style="display: none;">
        {{-- Statistics Overview --}}
        <div class="grid grid-cols-4 gap-4 mb-6">
            {{-- Visitors Stats --}}
            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Total Visitors</div>
                <div class="text-3xl font-bold" id="stat-total-visitors">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    <span class="text-green-600">+<span id="stat-new-today">-</span></span> today
                </div>
            </div>

            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">New This Week</div>
                <div class="text-3xl font-bold" id="stat-new-week">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    <span class="text-blue-600" id="stat-new-month">-</span> this month
                </div>
            </div>

            {{-- Sessions Stats --}}
            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Active Sessions</div>
                <div class="text-3xl font-bold" id="stat-active-sessions">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    <span class="text-gray-600" id="stat-sessions-today">-</span> today
                </div>
            </div>

            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Total Sessions</div>
                <div class="text-3xl font-bold" id="stat-total-sessions">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    All time
                </div>
            </div>
        </div>

        {{-- Rules & API Stats --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Active Rules</div>
                <div class="text-2xl font-bold" id="stat-active-rules">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    <span id="stat-total-rules">-</span> total
                </div>
            </div>

            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">API Connections</div>
                <div class="text-2xl font-bold" id="stat-active-api">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    <span id="stat-total-api">-</span> configured
                </div>
            </div>

            <div class="card p-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Rule Matches Today</div>
                <div class="text-2xl font-bold" id="stat-matches-today">-</div>
                <div class="text-xs text-gray-600 mt-2">
                    <span id="stat-total-matches">-</span> total
                </div>
            </div>
        </div>

        {{-- Recent Visitors --}}
        <div class="card p-0 mb-6">
            <div class="p-4 border-b">
                <h3 class="font-bold">Recent Visitors</h3>
            </div>
            <div class="p-4">
                <div id="recent-visitors-table"></div>
            </div>
        </div>

        {{-- Top Pages --}}
        <div class="card p-0">
            <div class="p-4 border-b">
                <h3 class="font-bold">Top Pages</h3>
            </div>
            <div class="p-4">
                <div id="top-pages-table"></div>
            </div>
        </div>

        {{-- Top Engaged Visitors --}}
        <div class="card p-0 mt-6">
            <div class="p-4 border-b">
                <h3 class="font-bold">{{ __('kai-personalize::messages.dashboard.top_engaged_visitors') }}</h3>
            </div>
            <div class="p-4">
                <div id="engaged-visitors-table"></div>
            </div>
        </div>

        {{-- Recent Sessions --}}
        <div class="card p-0 mt-6">
            <div class="p-4 border-b">
                <h3 class="font-bold">Recent Sessions</h3>
            </div>
            <div class="p-4">
                <div id="recent-sessions-table"></div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
    const DATA_URL = '{{ cp_route('kai-personalize.data') }}';

    function loadDashboardData() {
        fetch(DATA_URL)
            .then(response => response.json())
            .then(data => {
                // Update stats
                if (data.stats) {
                    document.getElementById('stat-total-visitors').textContent = data.stats.total_visitors?.toLocaleString() ?? '0';
                    document.getElementById('stat-new-today').textContent = data.stats.new_today?.toLocaleString() ?? '0';
                    document.getElementById('stat-new-week').textContent = data.stats.new_this_week?.toLocaleString() ?? '0';
                    document.getElementById('stat-new-month').textContent = data.stats.new_this_month?.toLocaleString() ?? '0';
                    document.getElementById('stat-active-sessions').textContent = data.stats.active_sessions?.toLocaleString() ?? '0';
                    document.getElementById('stat-sessions-today').textContent = data.stats.sessions_today?.toLocaleString() ?? '0';
                    document.getElementById('stat-total-sessions').textContent = data.stats.total_sessions?.toLocaleString() ?? '0';
                    document.getElementById('stat-active-rules').textContent = data.stats.active_rules ?? '0';
                    document.getElementById('stat-total-rules').textContent = data.stats.total_rules ?? '0';
                    document.getElementById('stat-active-api').textContent = data.stats.active_api_connections ?? '0';
                    document.getElementById('stat-total-api').textContent = data.stats.total_api_connections ?? '0';
                    document.getElementById('stat-matches-today').textContent = data.stats.rule_matches_today?.toLocaleString() ?? '0';
                    document.getElementById('stat-total-matches').textContent = data.stats.total_rule_matches?.toLocaleString() ?? '0';
                }

                // Render tables
                if (data.recentVisitors) renderRecentVisitors(data.recentVisitors);
                if (data.topPages) renderTopPages(data.topPages);
                if (data.topEngagedVisitors) renderEngagedVisitors(data.topEngagedVisitors);
                if (data.recentSessions) renderRecentSessions(data.recentSessions);

                // Show content
                document.getElementById('dashboard-loading').style.display = 'none';
                document.getElementById('dashboard-content').style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading dashboard data:', error);
                document.getElementById('dashboard-loading').innerHTML = '<p class="text-red-600">Error loading dashboard data: ' + error.message + '</p>';
            });
    }

    function renderRecentVisitors(visitors) {
        const container = document.getElementById('recent-visitors-table');
        if (!visitors || visitors.length === 0) {
            container.innerHTML = '<p class="text-gray-600 text-center py-4">No visitors yet</p>';
            return;
        }
        container.innerHTML = `
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th>Fingerprint</th>
                        <th>First Seen</th>
                        <th>Last Seen</th>
                        <th>Sessions</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    ${visitors.map(v => {
                        const visitorId = v.id ? String(v.id) : '';
                        return `
                        <tr>
                            <td>
                                <a href="/cp/kai-personalize/visitors/${visitorId}">
                                    <code class="text-xs text-blue-600 hover:underline">${v.fingerprint}</code>
                                </a>
                            </td>
                            <td class="text-xs">${v.first_seen}</td>
                            <td class="text-xs">${v.last_seen}</td>
                            <td>${v.sessions_count}</td>
                            <td>
                                <span class="badge-sm ${v.is_returning ? 'badge-info' : 'badge-success'}">
                                    ${v.is_returning ? 'Returning' : 'New'}
                                </span>
                            </td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
    }

    function renderTopPages(pages) {
        const container = document.getElementById('top-pages-table');
        if (!pages || pages.length === 0) {
            container.innerHTML = '<p class="text-gray-600 text-center py-4">No page views yet</p>';
            return;
        }
        container.innerHTML = `
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th class="text-right">Views</th>
                        <th class="text-right">Unique Visitors</th>
                    </tr>
                </thead>
                <tbody>
                    ${pages.map(p => {
                        // Generate link based on entry_slug or url_path
                        let pageLink;
                        if (p.entry_slug) {
                            pageLink = `/cp/kai-personalize/analytics/pages/${p.entry_slug}`;
                        } else {
                            pageLink = `/cp/kai-personalize/analytics/pages/detail?path=${encodeURIComponent(p.url_path || '/')}`;
                        }
                        return `
                        <tr>
                            <td>
                                <a href="${pageLink}" class="text-blue-600 hover:underline">
                                    ${p.name}
                                </a>
                            </td>
                            <td class="text-right font-bold">${p.views?.toLocaleString() ?? '0'}</td>
                            <td class="text-right">${p.unique_visitors?.toLocaleString() ?? '0'}</td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
    }

    function renderEngagedVisitors(visitors) {
        const container = document.getElementById('engaged-visitors-table');
        if (!visitors || visitors.length === 0) {
            container.innerHTML = '<p class="text-gray-600 text-center py-4">No visitors yet</p>';
            return;
        }
        container.innerHTML = `
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th>{{ __('kai-personalize::messages.visitors.fingerprint') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.engagement_score') }}</th>
                        <th>{{ __('kai-personalize::messages.dashboard.page_views') }}</th>
                        <th>{{ __('kai-personalize::messages.dashboard.sessions') }}</th>
                        <th>{{ __('kai-personalize::messages.visitors.last_visit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    ${visitors.map(v => {
                        const visitorId = v.id ? String(v.id) : '';
                        return `
                        <tr>
                            <td>
                                <a href="/cp/kai-personalize/visitors/${visitorId}">
                                    <code class="text-xs">${v.fingerprint}</code>
                                </a>
                            </td>
                            <td>
                                <span class="badge-sm ${v.score >= 70 ? 'badge-success' : (v.score >= 40 ? 'badge-info' : 'badge-warning')}">
                                    ${v.score}/100
                                </span>
                            </td>
                            <td>${v.page_views?.toLocaleString() ?? '0'}</td>
                            <td>${v.sessions}</td>
                            <td class="text-xs">${v.last_seen}</td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
    }

    function renderRecentSessions(sessions) {
        const container = document.getElementById('recent-sessions-table');
        if (!sessions || sessions.length === 0) {
            container.innerHTML = '<p class="text-gray-600 text-center py-4">No sessions yet</p>';
            return;
        }
        container.innerHTML = `
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Visitor ID</th>
                        <th>Started</th>
                        <th>Last Activity</th>
                        <th>Pages Viewed</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    ${sessions.map(s => {
                        const sessionId = s.id ? String(s.id).substring(0, 8) : '...';
                        const visitorId = s.visitor_id ? String(s.visitor_id) : '';
                        const visitorIdShort = visitorId ? visitorId.substring(0, 8) : '...';
                        return `
                        <tr>
                            <td><code class="text-xs">${sessionId}...</code></td>
                            <td>
                                <a href="/cp/kai-personalize/visitors/${visitorId}">
                                    <code class="text-xs text-blue-600 hover:underline">${visitorIdShort}...</code>
                                </a>
                            </td>
                            <td class="text-xs">${s.started || '-'}</td>
                            <td class="text-xs">${s.last_activity || '-'}</td>
                            <td>${s.pages_viewed ?? 0}</td>
                            <td>${s.duration || 'N/A'}</td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
    }

    // Load data when page is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    } else {
        loadDashboardData();
    }
</script>
