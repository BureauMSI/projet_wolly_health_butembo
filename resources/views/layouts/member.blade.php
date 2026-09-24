<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1a4730">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', __('messages.member_portal'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body data-select-search="{{ __('messages.search') }}" data-select-no-results="{{ __('messages.no_records') }}" data-sw-url="{{ asset('sw.js') }}">
    @php $memberUser = auth('member')->user(); @endphp
    <div class="staff-shell member-shell">
        <aside class="staff-sidebar d-none d-lg-flex">
            <a class="brand" href="{{ route('member.dashboard') }}">
                <span class="brand-mark">
                    @if($memberUser->photo_path)
                        <img class="member-app-avatar-img" src="{{ Storage::url($memberUser->photo_path) }}" alt="">
                    @else
                        {{ mb_strtoupper(mb_substr($memberUser->full_name, 0, 1)) }}
                    @endif
                </span>
                <span>
                    <strong>{{ $institution->name ?? __('messages.name') }}</strong>
                    <div class="small opacity-75">{{ $memberUser->full_name }}</div>
                </span>
            </a>
            @include('partials.member-menu')
        </aside>
        <div class="staff-main">
            <header class="staff-topbar">
                <button class="btn btn-outline-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#memberNav" aria-label="{{ __('messages.open_menu') }}">
                    <i class="bi bi-list"></i>
                </button>
                <div class="me-auto">
                    <div class="fw-semibold">@yield('title', __('messages.dashboard'))</div>
                    <div class="small text-muted d-none d-sm-block">{{ $memberUser->member_code }}</div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-topbar-ghost position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('messages.alerts') }}">
                        <i class="bi bi-bell"></i>
                        @if(($unreadMemberAlerts ?? 0) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-warning">{{ $unreadMemberAlerts }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end member-alert-menu">
                        <h2 class="dropdown-header">{{ __('messages.account_alerts') }}</h2>
                        @forelse($memberAlerts as $alert)
                            <a class="dropdown-item member-alert-item" href="{{ $alert['url'] }}">
                                <i class="bi {{ $alert['icon'] }}"></i>
                                <span>
                                    <strong>{{ $alert['title'] }}</strong>
                                    <span class="d-block small text-muted">{{ $alert['at']?->format('Y-m-d H:i') }} · {{ __('messages.'.$alert['status']) }}</span>
                                </span>
                            </a>
                        @empty
                            <span class="dropdown-item-text text-muted">{{ __('messages.no_alerts') }}</span>
                        @endforelse
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="{{ route('member.alerts') }}">{{ __('messages.see_all') }}</a>
                    </div>
                </div>
                @include('partials.topbar-tools', ['logoutRoute' => route('member.logout')])
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
    <div class="offcanvas offcanvas-start staff-offcanvas" tabindex="-1" id="memberNav">
        <div class="offcanvas-header">
            <h2 class="h5 text-white mb-0">{{ $institution->name ?? __('messages.name') }}</h2>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('partials.member-menu')
        </div>
    </div>
</body>
</html>
