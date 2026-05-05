@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    <form method="POST" action="{{ cp_route('kai-personalize.blacklists.update', $blacklist) }}">
        @csrf
        @method('PUT')
        @include('kai-personalize::blacklists._form', ['blacklist' => $blacklist])
    </form>
</div>
@endsection
