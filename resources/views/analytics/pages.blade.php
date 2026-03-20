@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    <div id="analytics-loading" class="text-center p-8">
        <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
        <p class="text-gray-600">Loading analytics data...</p>
    </div>

    <div id="analytics-content" style="display: none;">
        <div class="card p-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('kai-personalize::messages.analytics.pages.page') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.pages.views') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.pages.unique_visitors') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.pages.first_view') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.pages.last_view') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="analytics-table-body">
                </tbody>
            </table>
            <div id="analytics-pagination"></div>
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
    const DATA_URL = '{{ cp_route('kai-personalize.analytics.data') }}';
    let currentPage = 1;

    function loadAnalyticsData(page = 1) {
        fetch(`${DATA_URL}?page=${page}`)
            .then(response => response.json())
            .then(data => {
                renderAnalyticsTable(data.pages);
                renderPagination(data.pagination);

                document.getElementById('analytics-loading').style.display = 'none';
                document.getElementById('analytics-content').style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading analytics data:', error);
                document.getElementById('analytics-loading').innerHTML = '<p class="text-red-600">Error loading analytics data: ' + error.message + '</p>';
            });
    }

    function renderAnalyticsTable(pages) {
        const tbody = document.getElementById('analytics-table-body');
        if (!pages || pages.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray-600 py-4">No page views yet</td></tr>';
            return;
        }
        tbody.innerHTML = pages.map(page => `
            <tr>
                <td>
                    ${page.entry_title || page.url_path}
                    ${page.entry_title && page.url_path !== page.entry_title ? `<div class="text-xs text-gray-400">${page.url_path}</div>` : ''}
                </td>
                <td>${(page.views || 0).toLocaleString()}</td>
                <td>${(page.unique_visitors || 0).toLocaleString()}</td>
                <td class="text-xs">${page.first_view || '-'}</td>
                <td class="text-xs">${page.last_view || '-'}</td>
                <td>
                    ${page.entry_slug
                        ? `<a href="/cp/kai-personalize/analytics/pages/${page.entry_slug}" class="btn btn-sm">{{ __('kai-personalize::messages.analytics.pages.view_details') }}</a>`
                        : `<a href="/cp/kai-personalize/analytics/pages/detail?path=${encodeURIComponent(page.url_path)}" class="btn btn-sm">{{ __('kai-personalize::messages.analytics.pages.view_details') }}</a>`
                    }
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(pagination) {
        const container = document.getElementById('analytics-pagination');
        if (!pagination || pagination.total === 0) {
            container.innerHTML = '';
            return;
        }

        let html = '<div class="flex items-center justify-between p-4 border-t">';
        html += `<div class="text-sm text-gray-600">Showing ${(pagination.per_page * (pagination.current_page - 1) + 1).toLocaleString()} to ${Math.min(pagination.per_page * pagination.current_page, pagination.total).toLocaleString()} of ${pagination.total.toLocaleString()} results</div>`;

        if (pagination.links && pagination.links.length > 3) {
            html += '<div class="flex gap-1">';
            pagination.links.forEach((link, index) => {
                if (!link.url) {
                    html += `<span class="px-3 py-1 text-gray-400">${link.label}</span>`;
                } else if (link.active) {
                    html += `<span class="px-3 py-1 bg-blue-600 text-white">${link.label}</span>`;
                } else {
                    html += `<a href="#" class="px-3 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200" onclick="loadAnalyticsData(${index + 1}); return false;">${link.label}</a>`;
                }
            });
            html += '</div>';
        }

        html += '</div>';
        container.innerHTML = html;
    }

    // Load data when page is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => loadAnalyticsData(1));
    } else {
        loadAnalyticsData(1);
    }
</script>
