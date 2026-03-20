@extends('statamic::layout')

@section('title', __('kai-personalize::messages.rules.title'))

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ __('kai-personalize::messages.rules.title') }}</h1>
        <a href="{{ cp_route('kai-personalize.rules.create') }}" class="btn-primary">
            {{ __('kai-personalize::messages.rules.create') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-0">
        @if($rules->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Conditions</th>
                        <th>Created</th>
                        <th class="actions-column"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                        <tr>
                            <td>
                                <a href="{{ cp_route('kai-personalize.rules.show', $rule->id) }}" class="font-medium">
                                    {{ $rule->name }}
                                </a>
                            </td>
                            <td class="text-xs text-gray-600">
                                {{ Str::limit($rule->description, 50) }}
                            </td>
                            <td>
                                <span class="badge-sm badge-info">{{ $rule->priority }}</span>
                            </td>
                            <td>
                                <span class="badge-sm {{ $rule->is_active ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-xs">
                                {{ count($rule->conditions) }} condition(s)
                            </td>
                            <td class="text-xs text-gray-600">
                                {{ $rule->created_at->diffForHumans() }}
                            </td>
                            <td class="actions-column">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ cp_route('kai-personalize.rules.show', $rule->id) }}"
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        View
                                    </a>
                                    <a href="{{ cp_route('kai-personalize.rules.edit', $rule->id) }}"
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ cp_route('kai-personalize.rules.destroy', $rule->id) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this rule?');">
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
                {{ $rules->links() }}
            </div>
        @else
            <div class="p-8 text-center text-gray-600">
                <p class="mb-4">No personalization rules created yet.</p>
                <a href="{{ cp_route('kai-personalize.rules.create') }}" class="btn-primary">
                    Create Your First Rule
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
