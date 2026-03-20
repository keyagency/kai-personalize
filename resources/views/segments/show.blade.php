@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <div class="flex gap-2">
            <form method="POST" action="{{ cp_route('kai-personalize.segments.refresh', $segment->id) }}">
                @csrf
                <button type="submit" class="btn" onclick="return confirm('Re-evaluate all visitors for this segment?');">
                    Refresh Segment
                </button>
            </form>
            <a href="{{ cp_route('kai-personalize.segments.edit', $segment->id) }}" class="btn">
                Edit Segment
            </a>
            <form method="POST"
                  action="{{ cp_route('kai-personalize.segments.destroy', $segment->id) }}"
                  onsubmit="return confirm('Are you sure you want to delete this segment?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Total Visitors</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_visitors']) }}</div>
        </div>

        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Active Visitors</div>
            <div class="text-3xl font-bold">{{ number_format($stats['active_visitors']) }}</div>
            <div class="text-xs text-gray-600 mt-1">Last 30 days</div>
        </div>

        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">New Today</div>
            <div class="text-3xl font-bold">{{ number_format($stats['new_today']) }}</div>
        </div>
    </div>

    {{-- Segment Details --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">Segment Details</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Name</div>
                <div class="font-medium">{{ $segment->name }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Status</div>
                <span class="badge-sm {{ $segment->is_active ? 'badge-success' : 'badge-neutral' }}">
                    {{ $segment->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Created</div>
                <div class="text-sm">{{ $segment->created_at->format('M d, Y H:i') }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Last Updated</div>
                <div class="text-sm">{{ $segment->updated_at->format('M d, Y H:i') }}</div>
            </div>
        </div>

        @if($segment->description)
            <div class="mt-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Description</div>
                <div class="text-sm">{{ $segment->description }}</div>
            </div>
        @endif
    </div>

    {{-- Criteria --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">Criteria ({{ count($segment->criteria) }})</h3>

        @if(count($segment->criteria) > 0)
            <div class="space-y-3">
                @foreach($segment->criteria as $index => $condition)
                    <div class="border rounded p-3 bg-gray-50">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <div class="text-xs text-gray-600 uppercase mb-1">Attribute</div>
                                <code class="text-sm">{{ $condition['attribute'] ?? 'N/A' }}</code>
                            </div>
                            <div>
                                <div class="text-xs text-gray-600 uppercase mb-1">Operator</div>
                                <span class="badge-sm badge-info">{{ $condition['operator'] ?? 'equals' }}</span>
                            </div>
                            <div>
                                <div class="text-xs text-gray-600 uppercase mb-1">Value</div>
                                <code class="text-sm">
                                    @if(is_array($condition['value'] ?? null))
                                        {{ json_encode($condition['value']) }}
                                    @else
                                        {{ $condition['value'] ?? 'N/A' }}
                                    @endif
                                </code>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                <p class="text-xs text-blue-800">
                    <strong>Note:</strong> All criteria must be true for a visitor to be assigned to this segment (AND logic).
                </p>
            </div>
        @else
            <p class="text-gray-600">No criteria defined.</p>
        @endif
    </div>

    {{-- Recent Visitors --}}
    <div class="card p-4">
        <h3 class="font-bold mb-4">Recent Visitors ({{ $recentVisitors->count() }})</h3>

        @if($recentVisitors->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fingerprint</th>
                        <th>Visit Count</th>
                        <th>Last Visit</th>
                        <th>Assigned</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentVisitors as $visitor)
                        <tr>
                            <td>
                                <a href="{{ cp_route('kai-personalize.visitors.show', $visitor->id) }}" class="font-mono text-xs">
                                    {{ Str::limit($visitor->fingerprint_hash, 16) }}
                                </a>
                            </td>
                            <td>
                                <span class="badge-sm badge-info">{{ $visitor->visit_count }}</span>
                            </td>
                            <td class="text-xs">
                                {{ $visitor->last_visit_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="text-xs">
                                {{ $visitor->pivot?->assigned_at?->diffForHumans() ?? 'N/A' }}
                            </td>
                            <td class="actions-column">
                                <a href="{{ cp_route('kai-personalize.visitors.show', $visitor->id) }}"
                                   class="text-xs text-blue-600 hover:text-blue-800">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-600">No visitors assigned to this segment yet.</p>
        @endif
    </div>

    {{-- JSON View --}}
    <div class="card p-4 mt-4">
        <h3 class="font-bold mb-4">Raw Criteria (JSON)</h3>
        <pre class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto text-sm">{{ json_encode($segment->criteria, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
