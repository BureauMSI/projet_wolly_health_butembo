@php
    $membersOpen = request()->routeIs(
        'admin.members.index',
        'admin.members.show',
        'admin.members.create',
        'admin.members.edit',
        'admin.members.tree',
        'admin.placements.*',
        'admin.clients.*',
    );
    $salesOpen = request()->routeIs('admin.products.*', 'admin.sales.*');
    $financeOpen = request()->routeIs(
        'admin.operations.*',
        'admin.cash.*',
        'admin.commissions.*',
        'admin.payouts.*',
    );
    $reportsOpen = request()->routeIs('admin.reports.*');
    $branchOpen = request()->routeIs('admin.branches.show');
    $settingsOpen = request()->routeIs(
        'admin.institution.*',
        'admin.branches.index',
        'admin.branches.create',
        'admin.branches.edit',
        'admin.users.*',
        'admin.plan.*',
        'admin.whatsapp.*',
        'admin.sync.*',
    );
@endphp
<div class="staff-menu">
    <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <i class="bi bi-speedometer2" aria-hidden="true"></i>
        <span>{{ __('messages.dashboard') }}</span>
    </a>

    @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isCashier())
        <details class="staff-nav-group" @if($membersOpen) open @endif>
            <summary class="staff-nav-toggle">
                <span>
                    <i class="bi bi-people-fill staff-nav-icon" aria-hidden="true"></i>
                    <span>{{ __('messages.members') }}</span>
                </span>
                <i class="bi bi-chevron-down staff-nav-caret" aria-hidden="true"></i>
            </summary>
            <div class="staff-nav-items">
                <a class="{{ request()->routeIs('admin.members.index', 'admin.members.show', 'admin.members.create', 'admin.members.edit') ? 'active' : '' }}" href="{{ route('admin.members.index') }}">
                    <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                    <span>{{ __('messages.members') }}</span>
                </a>
                <a class="{{ request()->routeIs('admin.placements.*') ? 'active' : '' }}" href="{{ route('admin.placements.index') }}">
                    <i class="bi bi-signpost-2-fill" aria-hidden="true"></i>
                    <span>{{ __('messages.assignment') }}</span>
                </a>
                <a class="{{ request()->routeIs('admin.members.tree') ? 'active' : '' }}" href="{{ route('admin.members.tree') }}">
                    <i class="bi bi-diagram-3-fill" aria-hidden="true"></i>
                    <span>{{ __('messages.tree') }}</span>
                </a>
                <a class="{{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">
                    <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                    <span>{{ __('messages.clients') }}</span>
                </a>
            </div>
        </details>

        <details class="staff-nav-group" @if($salesOpen) open @endif>
            <summary class="staff-nav-toggle">
                <span>
                    <i class="bi bi-cart3 staff-nav-icon" aria-hidden="true"></i>
                    <span>{{ __('messages.sales') }}</span>
                </span>
                <i class="bi bi-chevron-down staff-nav-caret" aria-hidden="true"></i>
            </summary>
            <div class="staff-nav-items">
                <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <i class="bi bi-box-seam-fill" aria-hidden="true"></i>
                    <span>{{ __('messages.products') }}</span>
                </a>
                <a class="{{ request()->routeIs('admin.sales.*') ? 'active' : '' }}" href="{{ route('admin.sales.index') }}">
                    <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                    <span>{{ __('messages.sales') }}</span>
                </a>
            </div>
        </details>
    @endif

    <details class="staff-nav-group" @if($financeOpen) open @endif>
        <summary class="staff-nav-toggle">
            <span>
                <i class="bi bi-bank2 staff-nav-icon" aria-hidden="true"></i>
                <span>{{ __('messages.finance') }}</span>
            </span>
            <i class="bi bi-chevron-down staff-nav-caret" aria-hidden="true"></i>
        </summary>
        <div class="staff-nav-items">
            <a class="{{ request()->routeIs('admin.operations.*') ? 'active' : '' }}" href="{{ route('admin.operations.index') }}">
                <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                <span>{{ __('messages.cash_operations') }}</span>
            </a>
            <a class="{{ request()->routeIs('admin.cash.*') ? 'active' : '' }}" href="{{ route('admin.cash.index') }}">
                <i class="bi bi-safe2-fill" aria-hidden="true"></i>
                <span>{{ __('messages.cash') }}</span>
            </a>
            @if(auth()->user()->canViewCommissions())
                <a class="{{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}" href="{{ route('admin.commissions.index') }}">
                    <i class="bi bi-wallet2" aria-hidden="true"></i>
                    <span>{{ __('messages.commissions') }}</span>
                </a>
                <a class="{{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}" href="{{ route('admin.payouts.index') }}">
                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                    <span>{{ __('messages.payout_requests') }}</span>
                </a>
            @elseif(auth()->user()->isCashier())
                <a class="{{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}" href="{{ route('admin.payouts.index') }}">
                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                    <span>{{ __('messages.payout_requests') }}</span>
                </a>
            @endif
        </div>
    </details>

    @if(auth()->user()->canViewReports())
        <details class="staff-nav-group" @if($reportsOpen) open @endif>
            <summary class="staff-nav-toggle">
                <span>
                    <i class="bi bi-bar-chart-line-fill staff-nav-icon" aria-hidden="true"></i>
                    <span>{{ __('messages.reports') }}</span>
                </span>
                <i class="bi bi-chevron-down staff-nav-caret" aria-hidden="true"></i>
            </summary>
            <div class="staff-nav-items">
                <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                    <i class="bi bi-file-earmark-bar-graph-fill" aria-hidden="true"></i>
                    <span>{{ __('messages.reports') }}</span>
                </a>
            </div>
        </details>
    @endif

    @if(auth()->user()->isManager() && auth()->user()->branch_id)
        <details class="staff-nav-group" @if($branchOpen) open @endif>
            <summary class="staff-nav-toggle">
                <span>
                    <i class="bi bi-shop-window staff-nav-icon" aria-hidden="true"></i>
                    <span>{{ __('messages.branch') }}</span>
                </span>
                <i class="bi bi-chevron-down staff-nav-caret" aria-hidden="true"></i>
            </summary>
            <div class="staff-nav-items">
                <a class="{{ request()->routeIs('admin.branches.show') ? 'active' : '' }}" href="{{ route('admin.branches.show', auth()->user()->branch_id) }}">
                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <span>{{ __('messages.my_branch') }}</span>
                </a>
            </div>
        </details>
    @endif

    @if(auth()->user()->isAdmin() || auth()->user()->canUseWhatsappOutbox())
        <details class="staff-nav-group" @if($settingsOpen) open @endif>
            <summary class="staff-nav-toggle">
                <span>
                    <i class="bi bi-gear-fill staff-nav-icon" aria-hidden="true"></i>
                    <span>{{ __('messages.settings_nav') }}</span>
                </span>
                <i class="bi bi-chevron-down staff-nav-caret" aria-hidden="true"></i>
            </summary>
            <div class="staff-nav-items">
                @if(auth()->user()->isAdmin())
                    <a class="{{ request()->routeIs('admin.institution.*') ? 'active' : '' }}" href="{{ route('admin.institution.edit') }}">
                        <i class="bi bi-building" aria-hidden="true"></i>
                        <span>{{ __('messages.institution') }}</span>
                    </a>
                    <a class="{{ request()->routeIs('admin.branches.index', 'admin.branches.create', 'admin.branches.edit') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}">
                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                        <span>{{ __('messages.branches') }}</span>
                    </a>
                    <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-person-gear" aria-hidden="true"></i>
                        <span>{{ __('messages.users') }}</span>
                    </a>
                    <a class="{{ request()->routeIs('admin.plan.*') ? 'active' : '' }}" href="{{ route('admin.plan.edit') }}">
                        <i class="bi bi-sliders" aria-hidden="true"></i>
                        <span>{{ __('messages.plan_settings') }}</span>
                    </a>
                    <a class="{{ request()->routeIs('admin.sync.*') ? 'active' : '' }}" href="{{ route('admin.sync.index') }}">
                        <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i>
                        <span>{{ __('messages.sync') }}</span>
                    </a>
                @endif
                @if(auth()->user()->canUseWhatsappOutbox())
                    <a class="{{ request()->routeIs('admin.whatsapp.*') ? 'active' : '' }}" href="{{ route('admin.whatsapp.index') }}">
                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        <span>{{ __('messages.outbox') }}</span>
                    </a>
                @endif
            </div>
        </details>
    @endif
</div>
