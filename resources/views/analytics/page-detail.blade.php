@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <a href="{{ cp_route('kai-personalize.analytics.pages') }}" class="btn">
            ← Back
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">{{ __('kai-personalize::messages.analytics.pages.total_views') }}</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_views']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">{{ __('kai-personalize::messages.analytics.pages.unique_visitors') }}</div>
            <div class="text-3xl font-bold">{{ number_format($stats['unique_visitors']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">{{ __('kai-personalize::messages.analytics.avg_scroll_depth') }}</div>
            <div class="text-3xl font-bold">{{ round($stats['avg_scroll_depth']) }}%</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">{{ __('kai-personalize::messages.analytics.avg_reading_time') }}</div>
            <div class="text-3xl font-bold">{{ round($stats['avg_reading_time_ms'] / 1000) }}s</div>
        </div>
    </div>

    {{-- Recent Views --}}
    <div class="card p-4">
        <h3 class="font-bold mb-4">{{ __('kai-personalize::messages.analytics.pages.recent_views') }}</h3>
        @if($recentViews->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('kai-personalize::messages.visitors.fingerprint') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.collection') }}</th>
                        <th>{{ __('kai-personalize::messages.analytics.viewed_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentViews as $view)
                        <tr>
                            <td>
                                <a href="{{ cp_route('kai-personalize.visitors.show', $view->visitor_id) }}">
                                    <code class="text-xs">Visitor #{{ $view->visitor_id }}</code>
                                </a>
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
        @else
            <p class="text-gray-600">No views recorded.</p>
        @endif
    </div>
</div>
@endsection
