@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <div class="flex gap-2">
            <a href="{{ cp_route('kai-personalize.rules.edit', $rule->id) }}" class="btn">
                Edit Rule
            </a>
            <form method="POST"
                  action="{{ cp_route('kai-personalize.rules.destroy', $rule->id) }}"
                  onsubmit="return confirm('Are you sure you want to delete this rule?');">
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
            <div class="text-xs text-gray-600 uppercase mb-1">Total Matches</div>
            <div class="text-3xl font-bold">{{ number_format($stats['total_matches']) }}</div>
        </div>

        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Matches Today</div>
            <div class="text-3xl font-bold">{{ number_format($stats['matches_today']) }}</div>
        </div>

        <div class="card p-4">
            <div class="text-xs text-gray-600 uppercase mb-1">Last Matched</div>
            <div class="text-lg font-bold">{{ $stats['last_matched'] ?? 'Never' }}</div>
        </div>
    </div>

    {{-- Rule Details --}}
    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-4">Rule Details</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Name</div>
                <div class="font-medium">{{ $rule->name }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Status</div>
                <span class="badge-sm {{ $rule->is_active ? 'badge-success' : 'badge-neutral' }}">
                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Priority</div>
                <div class="font-medium">{{ $rule->priority }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-600 uppercase mb-1">Created</div>
                <div class="text-sm">{{ $rule->created_at->format('M d, Y H:i') }}</div>
            </div>
        </div>

        @if($rule->description)
            <div class="mt-4">
                <div class="text-xs text-gray-600 uppercase mb-1">Description</div>
                <div class="text-sm">{{ $rule->description }}</div>
            </div>
        @endif
    </div>

    {{-- Conditions --}}
    <div class="card p-4">
        <h3 class="font-bold mb-4">Conditions ({{ count($rule->conditions) }})</h3>

        @if(count($rule->conditions) > 0)
            <div class="space-y-3">
                @foreach($rule->conditions as $index => $condition)
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
                    <strong>Note:</strong> All conditions must be true for this rule to match (AND logic).
                </p>
            </div>
        @else
            <p class="text-gray-600">No conditions defined.</p>
        @endif
    </div>

    {{-- JSON View --}}
    <div class="card p-4 mt-4">
        <h3 class="font-bold mb-4">Raw Conditions (JSON)</h3>
        <pre class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto text-sm">{{ json_encode($rule->conditions, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
