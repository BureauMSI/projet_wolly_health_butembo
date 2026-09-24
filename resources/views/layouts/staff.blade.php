<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1a4730">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', $institution->name ?? __('messages.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body data-select-search="{{ __('messages.search') }}" data-select-no-results="{{ __('messages.no_records') }}" data-sw-url="{{ asset('sw.js') }}">
    <div class="staff-shell">
        <aside class="staff-sidebar d-none d-lg-flex">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark">{{ mb_substr($institution->acronym ?? 'HH', 0, 2) }}</span>
                <span>
                    <strong>{{ $institution->name ?? __('messages.name') }}</strong>
                    <div class="small opacity-75">{{ auth()->user()->name }}</div>
                </span>
            </a>
            @include('partials.staff-menu')
        </aside>
        <div class="staff-main">
            <header class="staff-topbar">
                <button class="btn btn-outline-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#staffNav" aria-label="{{ __('messages.open_menu') }}">
                    <i class="bi bi-list"></i>
                </button>
                <div class="me-auto">
                    <div class="fw-semibold">@yield('title', __('messages.dashboard'))</div>
                    <div class="small text-muted d-none d-sm-block">{{ auth()->user()->branch?->name ?? __('messages.all_branches') }}</div>
                </div>
                @include('partials.topbar-tools', ['logoutRoute' => route('logout')])
            </header>
            <div class="staff-content">
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-start staff-offcanvas" tabindex="-1" id="staffNav">
        <div class="offcanvas-header">
            <h2 class="h5 text-white mb-0">{{ $institution->name ?? __('messages.name') }}</h2>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('partials.staff-menu')
        </div>
    </div>
</body>
</html>
