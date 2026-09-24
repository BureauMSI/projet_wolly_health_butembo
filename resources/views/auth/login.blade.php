@extends('layouts.auth')

@section('content')
    @include('partials.auth-login-form', ['action' => route('login')])
@endsection

@push('scripts')
    @vite('resources/js/login-form.js')
@endpush
