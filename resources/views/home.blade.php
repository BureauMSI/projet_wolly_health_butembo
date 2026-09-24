@extends('layouts.app')

@section('content')
    <div class="guest-hero row align-items-center g-4 py-lg-5">
        <div class="col-lg-7">
            <p class="text-uppercase small fw-semibold text-success mb-2">{{ __('messages.footer_office') }}</p>
            <h1 class="display-5 fw-bold mb-3">{{ __('messages.name') }}</h1>
            <p class="lead">{{ __('messages.tagline') }}</p>
            <p class="text-muted">{{ __('messages.guest_intro') }}</p>
            <a class="btn btn-success btn-lg mt-2" href="{{ route('login') }}">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                {{ __('messages.sign_in') }}
            </a>
        </div>
        <div class="col-lg-5">
            <div class="card guest-card">
                <div class="card-body p-4">
                    <h2 class="h5 text-success mb-2">{{ __('messages.login') }}</h2>
                    <p class="text-muted mb-0">{{ __('messages.login_hint') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
