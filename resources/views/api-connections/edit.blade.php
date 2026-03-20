@extends('statamic::layout')

@section('title', $title)

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <a href="{{ cp_route('kai-personalize.api-connections.show', $connection->id) }}" class="btn">
            View Connection
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ cp_route('kai-personalize.api-connections.update', $connection->id) }}">
        @csrf
        @method('PUT')
        @include('kai-personalize::api-connections._form', ['connection' => $connection])
    </form>
@endsection
