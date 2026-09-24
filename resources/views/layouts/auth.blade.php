<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1a4730">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>{{ __('messages.login') }} — {{ $institution->name ?? __('messages.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="auth-screen" data-sw-url="{{ asset('sw.js') }}">
    <div class="auth-panel">
        <div class="auth-hero">
            @php
                $mark = mb_strtoupper(mb_substr($institution->acronym ?? $institution->name ?? 'HH', 0, 2));
                $logo = $institution->logo_path ?? null;
            @endphp
            <div class="auth-mark">
                @if($logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($logo) }}" alt="">
                @else
                    {{ $mark }}
                @endif
            </div>
            <h1>{{ $institution->name ?? __('messages.name') }}</h1>
            <p>{{ __('messages.login_hint') }}</p>
        </div>
        <main class="auth-sheet">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
            <div class="auth-langs">
                <a class="{{ app()->getLocale() === 'fr' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'fr') }}">{{ __('messages.french') }}</a>
                <a class="{{ app()->getLocale() === 'sw' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'sw') }}">{{ __('messages.swahili') }}</a>
            </div>
        </main>
    </div>
</body>
</html>
