<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1a4730">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>{{ __('messages.member_login') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="member-auth" data-sw-url="{{ asset('sw.js') }}">
    <div class="member-auth-hero">
        <div class="member-auth-mark">{{ mb_strtoupper(mb_substr($institution->acronym ?? $institution->name ?? 'HH', 0, 2)) }}</div>
        <h1>{{ $institution->name ?? __('messages.name') }}</h1>
        <p>{{ __('messages.member_login') }}</p>
    </div>
    <main class="member-auth-sheet">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @yield('content')
        <div class="member-auth-langs">
            <a class="{{ app()->getLocale() === 'fr' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'fr') }}">{{ __('messages.french') }}</a>
            <a class="{{ app()->getLocale() === 'sw' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'sw') }}">{{ __('messages.swahili') }}</a>
        </div>
    </main>
</body>
</html>
