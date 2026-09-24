<div class="topbar-tools">
    <div class="dropdown">
        <button class="btn btn-sm btn-topbar-ghost dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('messages.switch_language') }}">
            <i class="bi bi-translate"></i>
            <span class="d-none d-sm-inline">{{ strtoupper(app()->getLocale()) }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}">{{ __('messages.french') }}</a>
            </li>
            <li>
                <a class="dropdown-item {{ app()->getLocale() === 'sw' ? 'active' : '' }}" href="{{ route('locale.switch', 'sw') }}">{{ __('messages.swahili') }}</a>
            </li>
        </ul>
    </div>
    <form method="post" action="{{ $logoutRoute }}" class="d-inline">
        @csrf
        <button class="btn btn-sm btn-topbar-danger" type="submit" title="{{ __('messages.logout') }}">
            <i class="bi bi-box-arrow-right"></i>
            <span class="d-none d-md-inline">{{ __('messages.logout') }}</span>
        </button>
    </form>
</div>
