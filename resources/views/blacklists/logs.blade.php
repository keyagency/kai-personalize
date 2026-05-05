@extends('statamic::layout')

@section('title', $title)

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">
            <a href="{{ cp_route('kai-personalize.blacklists.index') }}" class="text-gray-500 hover:text-gray-700">
                {{ __('kai-personalize::messages.blacklists.title') }}
            </a>
            <span class="text-gray-400 mx-2">/</span>
            {{ $blacklist->pattern }}
        </h1>
        <span class="badge-sm {{ $blacklist->is_active ? 'badge-success' : 'badge-neutral' }}">
            {{ $blacklist->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <div class="card mb-4 p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-600 uppercase">Type</div>
                <div class="font-bold">{{ $blacklist->type === 'bot_name' ? 'Bot Name' : 'User Agent Pattern' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-600 uppercase">Pattern</div>
                <div class="font-bold"><code>{{ $blacklist->pattern }}</code></div>
            </div>
            <div>
                <div class="text-xs text-gray-600 uppercase">Description</div>
                <div>{{ $blacklist->description ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-600 uppercase">Total Hits</div>
                <div class="font-bold">{{ $blacklist->hit_count }}</div>
            </div>
        </div>
    </div>

    <div class="card p-0">
        @if($logs->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Bot Name</th>
                        <th>User Agent</th>
                        <th>IP Address</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="text-sm">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="text-sm">{{ $log->bot_name ?? '-' }}</td>
                            <td class="text-xs text-gray-600 max-w-xs truncate" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?? '-' }}
                            </td>
                            <td class="text-sm">{{ $log->ip_address ?? '-' }}</td>
                            <td class="text-xs text-gray-600 max-w-xs truncate" title="{{ $log->url }}">
                                {{ $log->url ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($logs->hasPages())
                <div class="p-4">
                    {{ $logs->appends(['page' => request()->get('page')])->links() }}
                </div>
            @endif
        @else
            <div class="p-8 text-center text-gray-600">
                <p>No log entries found for this blacklist entry.</p>
            </div>
        @endif
    </div>
@endsection
