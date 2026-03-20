@extends('statamic::layout')

@section('title', $title)

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
        <a href="{{ cp_route('kai-personalize.rules.show', $rule->id) }}" class="btn">
            View Rule
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ cp_route('kai-personalize.rules.update', $rule->id) }}">
        @csrf
        @method('PUT')
        @include('kai-personalize::rules._form', ['rule' => $rule])
    </form>
</div>
@endsection
