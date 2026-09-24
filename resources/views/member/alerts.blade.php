@extends('layouts.member')
@section('title', __('messages.alerts'))
@section('content')
    <div class="member-page-head">
        <h1><i class="bi bi-bell"></i> {{ __('messages.account_alerts') }}</h1>
        <p>{{ __('messages.alerts_hint') }}</p>
    </div>
    <section class="member-sheet">
        @forelse($alerts as $alert)
            <a class="member-row text-decoration-none text-reset" href="{{ $alert['url'] }}">
                <div class="member-row-main">
                    <i class="bi {{ $alert['icon'] }}"></i>
                    <div>
                        <strong>{{ $alert['title'] }}</strong>
                        <div class="text-muted">{{ $alert['at']?->format('Y-m-d H:i') }} · {{ __('messages.'.$alert['status']) }}</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
            </a>
        @empty
            <p class="text-muted mb-0">{{ __('messages.no_alerts') }}</p>
        @endforelse
    </section>
@endsection
