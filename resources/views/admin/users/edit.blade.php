@extends('layouts.staff')
@section('title', __('messages.edit_user'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.edit_user') }}</h1>
        <a class="btn btn-outline-success" href="{{ route('admin.users.index') }}">{{ __('messages.back') }}</a>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.users.update', $user) }}" class="row g-3" id="user-form">
                @csrf
                @method('PUT')
                @include('admin.users._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    @vite('resources/js/user-form.js')
@endpush
