@extends('statamic::layout')

@section('title', $title)

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    <form method="POST" action="{{ cp_route('kai-personalize.api-connections.store') }}">
        @csrf
        @include('kai-personalize::api-connections._form', ['connection' => null])
    </form>
@endsection
