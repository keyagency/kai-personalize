@extends('statamic::layout')

@section('title', __('kai-personalize::messages.visitors.title'))

@section('content')
<div class="kai-personalize">
        <div class="flex items-center justify-between mb-3">
            <h1 class="flex-1">{{ __('kai-personalize::messages.visitors.title') }}</h1>
        </div>

    {{-- Filters --}}
    <form method="GET" class="card p-4 mb-4">
        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="text-xs font-bold">Search Fingerprint</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="input-text input-sm" />
            </div>
            <div>
                <label class="text-xs font-bold">From Date</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input-text input-sm" />
            </div>
            <div>
                <label class="text-xs font-bold">To Date</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input-text input-sm" />
            </div>
            <div>
                <label class="text-xs font-bold">&nbsp;</label>
                <div class="flex gap-2">
                    <button type="submit" class="btn-sm btn-primary">Filter</button>
                    <a href="{{ cp_route('kai-personalize.visitors.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                </div>
            </div>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card p-0">
        @if($visitors->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fingerprint</th>
                        <th>First Visit</th>
                        <th>Last Visit</th>
                        <th>Visits</th>
                        <th>Type</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visitors as $visitor)
                        <tr>
                            <td>
                                <a href="{{ cp_route('kai-personalize.visitors.show', $visitor->id) }}" class="font-mono text-xs">
                                    {{ substr($visitor->fingerprint_hash, 0, 16) }}...
                                </a>
                            </td>
                            <td class="text-xs">{{ $visitor->first_visit_at?->format('M d, Y H:i') }}</td>
                            <td class="text-xs">{{ $visitor->last_visit_at?->diffForHumans() }}</td>
                            <td>{{ $visitor->visit_count }}</td>
                            <td>
                                <span class="badge-sm {{ $visitor->visit_count > 1 ? 'badge-info' : 'badge-success' }}">
                                    {{ $visitor->visit_count > 1 ? 'Returning' : 'New' }}
                                </span>
                            </td>
                            <td class="actions-column">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ cp_route('kai-personalize.visitors.show', $visitor->id) }}"
                                       class="btn btn-sm">View</a>
                                    <form method="POST"
                                          action="{{ cp_route('kai-personalize.visitors.destroy', $visitor->id) }}"
                                          onsubmit="return confirm('Delete this visitor and all associated data?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $visitors->links() }}</div>
        @else
            <div class="p-8 text-center text-gray-600">
                <p>No visitors found.</p>
            </div>
        @endif
    </div>
</div>
@endsection
