@extends('layouts.staff')
@section('title', __('messages.new_user'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.new_user') }}</h1>
        <a class="btn btn-outline-success" href="{{ route('admin.users.index') }}">{{ __('messages.back') }}</a>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <p class="text-muted">{{ __('messages.roles_hint') }}</p>
            <form method="post" action="{{ route('admin.users.store') }}" class="row g-3" id="user-form">
                @csrf
                @include('admin.users._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    @vite('resources/js/user-form.js')
@endpush
