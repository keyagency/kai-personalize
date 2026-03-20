@extends('statamic::layout')

@section('title', __('kai-personalize::messages.api_connections.title'))

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ __('kai-personalize::messages.api_connections.title') }}</h1>
        <a href="{{ cp_route('kai-personalize.api-connections.create') }}" class="btn-primary">
            Create Connection
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Total Connections</div>
            <div class="text-3xl font-bold">{{ $stats['total'] }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Active</div>
            <div class="text-3xl font-bold text-green-600">{{ $stats['active'] }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Inactive</div>
            <div class="text-3xl font-bold text-gray-400">{{ $stats['inactive'] }}</div>
        </div>
    </div>

    <div class="card p-0">
        @if($connections->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Provider</th>
                        <th>Auth Type</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Cache Duration</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($connections as $conn)
                        <tr>
                            <td>
                                <a href="{{ cp_route('kai-personalize.api-connections.show', $conn->id) }}" class="font-medium">
                                    {{ $conn->name }}
                                </a>
                            </td>
                            <td><span class="badge-sm badge-info">{{ $conn->provider }}</span></td>
                            <td class="text-xs">{{ $conn->auth_type }}</td>
                            <td>
                                <span class="badge-sm {{ $conn->is_active ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $conn->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-xs">{{ $conn->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="text-xs">{{ $conn->cache_duration }}s</td>
                            <td class="actions-column">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ cp_route('kai-personalize.api-connections.show', $conn->id) }}"
                                       class="text-xs text-blue-600">View</a>
                                    <a href="{{ cp_route('kai-personalize.api-connections.edit', $conn->id) }}"
                                       class="text-xs text-blue-600">Edit</a>
                                    <form method="POST" action="{{ cp_route('kai-personalize.api-connections.test', $conn->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-green-600">Test</button>
                                    </form>
                                    <form method="POST"
                                          action="{{ cp_route('kai-personalize.api-connections.destroy', $conn->id) }}"
                                          onsubmit="return confirm('Delete this API connection?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center text-gray-600">
                <p class="mb-4">No API connections configured yet.</p>
                <a href="{{ cp_route('kai-personalize.api-connections.create') }}" class="btn-primary">
                    Create Your First Connection
                </a>
            </div>
        @endif
    </div>
@endsection
