@extends('statamic::layout')

@section('title', __('kai-personalize::messages.segments.title'))

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ __('kai-personalize::messages.segments.title') }}</h1>
        <a href="{{ cp_route('kai-personalize.segments.create') }}" class="btn-primary">
            {{ __('kai-personalize::messages.segments.create') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-0">
        @if($segments->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Visitors</th>
                        <th>Status</th>
                        <th>Criteria</th>
                        <th>Created</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($segments as $segment)
                        <tr>
                            <td>
                                <a href="{{ cp_route('kai-personalize.segments.show', $segment->id) }}" class="font-medium">
                                    {{ $segment->name }}
                                </a>
                            </td>
                            <td class="text-xs text-gray-600">
                                {{ Str::limit($segment->description, 50) }}
                            </td>
                            <td>
                                <span class="badge-sm badge-info">{{ number_format($segment->visitor_count) }}</span>
                            </td>
                            <td>
                                <span class="badge-sm {{ $segment->is_active ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $segment->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-xs">
                                {{ count($segment->criteria) }} condition(s)
                            </td>
                            <td class="text-xs text-gray-600">
                                {{ $segment->created_at->diffForHumans() }}
                            </td>
                            <td class="actions-column">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ cp_route('kai-personalize.segments.show', $segment->id) }}"
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        View
                                    </a>
                                    <a href="{{ cp_route('kai-personalize.segments.edit', $segment->id) }}"
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ cp_route('kai-personalize.segments.destroy', $segment->id) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this segment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $segments->links() }}
            </div>
        @else
            <div class="p-8 text-center text-gray-600">
                <p class="mb-4">No visitor segments created yet.</p>
                <a href="{{ cp_route('kai-personalize.segments.create') }}" class="btn-primary">
                    Create Your First Segment
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
