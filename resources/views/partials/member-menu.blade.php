<div class="staff-menu">
    <span class="nav-label">{{ __('messages.account') }}</span>
    <a class="{{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}"><i class="bi bi-house-door"></i><span>{{ __('messages.nav_home') }}</span></a>
    <a class="{{ request()->routeIs('member.network') ? 'active' : '' }}" href="{{ route('member.network') }}"><i class="bi bi-diagram-3"></i><span>{{ __('messages.nav_tree') }}</span></a>
    <a class="{{ request()->routeIs('member.history') ? 'active' : '' }}" href="{{ route('member.history') }}"><i class="bi bi-clock-history"></i><span>{{ __('messages.nav_history') }}</span></a>
    <a class="{{ request()->routeIs('member.purchases') ? 'active' : '' }}" href="{{ route('member.purchases') }}"><i class="bi bi-bag"></i><span>{{ __('messages.nav_shop') }}</span></a>
    <a class="{{ request()->routeIs('member.reports.*') ? 'active' : '' }}" href="{{ route('member.reports.index') }}"><i class="bi bi-printer"></i><span>{{ __('messages.reports') }}</span></a>
    <span class="nav-label">{{ __('messages.alerts') }}</span>
    <a class="{{ request()->routeIs('member.alerts') ? 'active' : '' }}" href="{{ route('member.alerts') }}"><i class="bi bi-bell"></i><span>{{ __('messages.account_alerts') }}</span></a>
    <a class="{{ request()->routeIs('member.profile') ? 'active' : '' }}" href="{{ route('member.profile') }}"><i class="bi bi-person"></i><span>{{ __('messages.nav_profile') }}</span></a>
</div>
