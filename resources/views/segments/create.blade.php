@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    <form method="POST" action="{{ cp_route('kai-personalize.segments.store') }}">
        @csrf
        @include('kai-personalize::segments._form')
    </form>
</div>
@endsection
