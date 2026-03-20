@extends('statamic::layout')

@section('title', $title)

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <div class="flex gap-2">
            <form method="POST" action="{{ cp_route('kai-personalize.api-connections.test', $connection->id) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-primary">Test Connection</button>
            </form>
            <a href="{{ cp_route('kai-personalize.api-connections.edit', $connection->id) }}" class="btn">
                Edit
            </a>
            <form method="POST"
                  action="{{ cp_route('kai-personalize.api-connections.destroy', $connection->id) }}"
                  onsubmit="return confirm('Delete this API connection?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Total Requests</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_requests']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Requests Today</div>
            <div class="text-3xl font-bold">{{ number_format($stats['requests_today']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Cache Entries</div>
            <div class="text-3xl font-bold">{{ number_format($stats['cache_entries']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Success Rate</div>
            <div class="text-3xl font-bold">{{ $stats['success_rate'] }}</div>
        </div>
    </div>

    {{-- Connection Details --}}
    <div class="card p-4 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold">Connection Details</h3>
            @if($stats['cache_entries'] > 0)
                <form method="POST" action="{{ cp_route('kai-personalize.api-connections.clear-cache', $connection->id) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-600" onclick="return confirm('Clear all cache entries for this connection?');">
                        Clear Cache
                    </button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Name</div>
                <div class="font-medium">{{ $connection->name }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Provider</div>
                <span class="badge badge-info">{{ $connection->provider }}</span>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Status</div>
                <span class="badge {{ $connection->is_active ? 'badge-success' : 'badge-neutral' }}">
                    {{ $connection->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Last Used</div>
                <div class="text-sm">{{ $stats['last_used'] ?? 'Never' }}</div>
            </div>

            <div class="col-span-2">
                <div class="text-xs text-gray-600 uppercase mb-1">API URL</div>
                <code class="text-xs">{{ $connection->api_url }}</code>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Authentication</div>
                <span class="badge-sm badge-neutral">{{ $connection->auth_type }}</span>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Has API Key</div>
                <span class="badge-sm {{ $connection->api_key ? 'badge-success' : 'badge-neutral' }}">
                    {{ $connection->api_key ? 'Yes' : 'No' }}
                </span>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Timeout</div>
                <div class="text-sm">{{ $connection->timeout }}s</div>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Cache Duration</div>
                <div class="text-sm">{{ $connection->cache_duration }}s</div>
            </div>

            @if($connection->rate_limit)
                <div>
                    <div class="text-xs text-gray-600 uppercase mb-1">Rate Limit</div>
                    <div class="text-sm">{{ $connection->rate_limit }} req/min</div>
                </div>
            @endif

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Created</div>
                <div class="text-sm">{{ $connection->created_at->format('M d, Y H:i') }}</div>
            </div>
        </div>

        @if($connection->headers)
            <div class="mt-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Custom Headers</div>
                <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto">{{ json_encode($connection->headers, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($connection->auth_config)
            <div class="mt-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Auth Configuration</div>
                <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto">{{ json_encode($connection->auth_config, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    </div>

    {{-- Recent API Logs --}}
    <div class="card p-4">
        <h3 class="font-bold mb-4">Recent API Calls ({{ $recentLogs->count() }})</h3>

        @if($recentLogs->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                        <tr>
                            <td class="text-xs"><code>{{ Str::limit($log->endpoint ?? 'N/A', 50) }}</code></td>
                            <td><span class="badge-sm badge-neutral">{{ $log->method ?? 'GET' }}</span></td>
                            <td>
                                <span class="badge-sm {{ $log->status_code >= 200 && $log->status_code < 300 ? 'badge-success' : 'badge-danger' }}">
                                    {{ $log->status_code ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-xs">{{ $log->response_time ?? 'N/A' }}ms</td>
                            <td class="text-xs">{{ $log->created_at?->format('M d, H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-600">No API calls logged yet.</p>
        @endif
    </div>
@endsection
