@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <a href="{{ cp_route('kai-personalize.blacklists.create') }}" class="btn btn-primary">
            {{ __('kai-personalize::messages.blacklists.add') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card p-0">
        @if($blacklists->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('kai-personalize::messages.blacklists.type') }}</th>
                        <th>{{ __('kai-personalize::messages.blacklists.pattern') }}</th>
                        <th>{{ __('kai-personalize::messages.blacklists.description') }}</th>
                        <th>{{ __('kai-personalize::messages.blacklists.is_active') }}</th>
                        <th>{{ __('kai-personalize::messages.blacklists.hit_count') }}</th>
                        <th>{{ __('kai-personalize::messages.blacklists.last_hit') }}</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blacklists as $blacklist)
                        <tr>
                            <td>
                                <span class="badge-sm @if($blacklist->type === 'bot_name') badge-info @else badge-warning @endif">
                                    {{ $blacklist->type === 'bot_name' ? 'Bot Name' : 'User Agent' }}
                                </span>
                            </td>
                            <td><code class="text-sm">{{ $blacklist->pattern }}</code></td>
                            <td class="text-sm">{{ $blacklist->description ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ cp_route('kai-personalize.blacklists.toggle', $blacklist) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="badge-sm {{ $blacklist->is_active ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $blacklist->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-sm">{{ $blacklist->hit_count }}</td>
                            <td class="text-sm">{{ $blacklist->last_hit_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="actions-column">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ cp_route('kai-personalize.blacklists.logs', $blacklist) }}"
                                       class="btn btn-sm">Logs</a>
                                    <a href="{{ cp_route('kai-personalize.blacklists.edit', $blacklist) }}"
                                       class="btn btn-sm">Edit</a>
                                    <form method="POST"
                                          action="{{ cp_route('kai-personalize.blacklists.destroy', $blacklist) }}"
                                          onsubmit="return confirm('Delete this blacklist entry?');" class="inline">
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
        @else
            <div class="p-8 text-center text-gray-600">
                <p class="mb-4">{{ __('kai-personalize::messages.blacklists.empty') }}</p>
                <a href="{{ cp_route('kai-personalize.blacklists.create') }}" class="btn btn-primary">
                    {{ __('kai-personalize::messages.blacklists.add_first') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
