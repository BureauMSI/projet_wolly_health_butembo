<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $institution->name ?? __('messages.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-body">
    <nav class="navbar navbar-expand-lg guest-nav">
        <div class="container">
            <a class="navbar-brand hh-brand" href="{{ url('/') }}">
                <span class="guest-brand-mark">{{ mb_substr($institution->acronym ?? 'HH', 0, 2) }}</span>
                {{ $institution->name ?? __('messages.name') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guestNav" aria-label="{{ __('messages.open_menu') }}">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="guestNav">
                <div class="ms-auto d-flex flex-wrap gap-2 py-2 py-lg-0 align-items-center">
                    <a class="btn btn-sm btn-light" href="{{ route('login') }}">{{ __('messages.sign_in') }}</a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ strtoupper(app()->getLocale()) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}">{{ __('messages.french') }}</a></li>
                            <li><a class="dropdown-item {{ app()->getLocale() === 'sw' ? 'active' : '' }}" href="{{ route('locale.switch', 'sw') }}">{{ __('messages.swahili') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main class="container py-4 guest-main">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
