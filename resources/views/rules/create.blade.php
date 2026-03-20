@extends('statamic::layout')

@section('title', __('kai-personalize::messages.rules.create'))

@section('content')
<div class="kai-personalize">
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ __('kai-personalize::messages.rules.create') }}</h1>
    </div>

    <form method="POST" action="{{ cp_route('kai-personalize.rules.store') }}">
        @csrf
        @include('kai-personalize::rules._form', ['rule' => null])
    </form>
</div>
@endsection
