@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <form method="POST" action="{{ cp_route('kai-personalize.visitors.destroy', $visitor->id) }}"
              onsubmit="return confirm('Delete this visitor and all data?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete Visitor</button>
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Total Visits</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_sessions']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Page Views</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_page_views']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Attributes</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_attributes']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Rule Matches</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_rule_matches']) }}</div>
        </div>
    </div>

    {{-- Engagement Score --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">{{ __('kai-personalize::messages.analytics.engagement_score') }}</h3>
        <div class="flex items-center gap-4">
            <div class="text-5xl font-bold {{ $stats['engagement_score'] >= 70 ? 'text-green-600' : ($stats['engagement_score'] >= 40 ? 'text-yellow-600' : 'text-gray-600') }}">
                {{ $stats['engagement_score'] }}
            </div>
            <div class="text-sm text-gray-600">
                {{ __('kai-personalize::messages.analytics.engagement_score_description') }}
            </div>
        </div>
    </div>

    {{-- Behavioral Summary --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">{{ __('kai-personalize::messages.analytics.behavioral_summary') }}</h3>
        <div class="grid grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-600">{{ __('kai-personalize::messages.analytics.max_scroll_depth') }}</div>
                <div class="text-xl font-bold">{{ $behaviorSummary['max_scroll_depth'] }}%</div>
            </div>
            <div>
                <div class="text-xs text-gray-600">{{ __('kai-personalize::messages.analytics.reading_time') }}</div>
                <div class="text-xl font-bold">{{ round($behaviorSummary['total_reading_time_ms'] / 60000) }} min</div>
            </div>
            <div>
                <div class="text-xs text-gray-600">{{ __('kai-personalize::messages.analytics.total_clicks') }}</div>
                <div class="text-xl font-bold">{{ $behaviorSummary['total_clicks'] }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-600">{{ __('kai-personalize::messages.analytics.total_events') }}</div>
                <div class="text-xl font-bold">{{ $behaviorSummary['total_events'] }}</div>
            </div>
        </div>
    </div>

    {{-- Page History --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">{{ __('kai-personalize::messages.analytics.page_history') }}</h3>
        @if($pageHistory->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('kai-personalize::messages.analytics.page') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.collection') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.viewed_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pageHistory as $view)
                        <tr>
                            <td>
                                {{ $view->entry_title ?: $view->url_path }}
                            </td>
                            <td>
                                @if($view->collection_handle)
                                    <span class="badge-sm badge-info">{{ $view->collection_handle }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-xs">{{ $view->viewed_at->format('M d, H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $pageHistory->links() }}
        @else
            <p class="text-gray-600">No page views recorded.</p>
        @endif
    </div>

    {{-- Visitor Details --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">Visitor Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Fingerprint Hash</div>
                <code class="text-xs">{{ $visitor->fingerprint_hash }}</code>
            </div>
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Type</div>
                <span class="badge {{ $stats['returning'] ? 'badge-info' : 'badge-success' }}">
                    {{ $stats['returning'] ? 'Returning Visitor' : 'New Visitor' }}
                </span>
            </div>
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">First Visit</div>
                <div class="text-sm">{{ $stats['first_visit']?->format('M d, Y H:i') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Last Visit</div>
                <div class="text-sm">{{ $stats['last_visit']?->diffForHumans() }}</div>
            </div>
        </div>
    </div>

    {{-- Attributes --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">Visitor Attributes ({{ $attributes->count() }})</h3>
        @if($attributes->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Type</th>
                        <th>Expires</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attributes as $attr)
                        <tr>
                            <td><code class="text-xs">{{ $attr->attribute_key }}</code></td>
                            <td class="text-sm">{{ $attr->attribute_value }}</td>
                            <td><span class="badge-sm badge-info">{{ $attr->attribute_type }}</span></td>
                            <td class="text-xs">{{ $attr->expires_at ? $attr->expires_at->diffForHumans() : 'Never' }}</td>
                            <td class="actions-column">
                                @if($attr->attribute_key === 'bot_name')
                                    <form method="POST" action="{{ cp_route('kai-personalize.visitors.blacklist', $visitor->id) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="bot_name">
                                        <input type="hidden" name="pattern" value="{{ $attr->attribute_value }}">
                                        <button type="submit" class="btn btn-danger btn-sm">Blacklist</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-600">No custom attributes set.</p>
        @endif
    </div>

    {{-- Recent Sessions --}}
    <div class="card p-4">
        <h3 class="font-bold mb-4">Recent Sessions ({{ $recentSessions->count() }})</h3>
        @if($recentSessions->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>User Agent</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th>Page Views</th>
                        <th>Duration</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSessions as $session)
                        <tr>
                            <td><code class="text-xs">{{ substr($session->session_id, 0, 16) }}...</code></td>
                            <td class="text-xs max-w-xs truncate" title="{{ $session->user_agent }}">
                                {{ $session->user_agent ? Str::limit($session->user_agent, 60) : '-' }}
                            </td>
                            <td class="text-xs">{{ $session->started_at?->format('M d, H:i') }}</td>
                            <td class="text-xs">{{ $session->ended_at ? $session->ended_at->format('M d, H:i') : 'Active' }}</td>
                            <td>{{ $session->page_views }}</td>
                            <td class="text-xs">
                                {{ $session->ended_at ? $session->started_at->diffInMinutes($session->ended_at) . ' min' : 'N/A' }}
                            </td>
                            <td class="actions-column">
                                @if($session->user_agent)
                                    <form method="POST" action="{{ cp_route('kai-personalize.visitors.blacklist', $visitor->id) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="user_agent">
                                        <input type="hidden" name="pattern" value="{{ $session->user_agent }}">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Blacklist user agent: {{ addslashes(Str::limit($session->user_agent, 60)) }}?')">
                                            Blacklist UA
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-600">No sessions recorded.</p>
        @endif
    </div>
</div>
@endsection
