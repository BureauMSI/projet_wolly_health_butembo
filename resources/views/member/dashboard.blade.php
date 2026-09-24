@extends('layouts.member')
@section('title', __('messages.dashboard'))
@section('content')
    <div class="member-hello">
        <div>
            <p class="member-hello-kicker">{{ __('messages.member_portal') }}</p>
            <h1>{{ __('messages.hello_member', ['name' => $member->full_name]) }}</h1>
            <p>{{ __('messages.offline_note') }}</p>
        </div>
        <div class="member-hello-badge"><i class="bi bi-person-badge"></i> {{ $member->member_code }}</div>
    </div>
    <div class="member-shortcuts">
        <a href="{{ route('member.network') }}"><i class="bi bi-diagram-3"></i><span>{{ __('messages.nav_tree') }}</span></a>
        <a href="{{ route('member.history') }}"><i class="bi bi-clock-history"></i><span>{{ __('messages.nav_history') }}</span></a>
        <a href="{{ route('member.purchases') }}"><i class="bi bi-bag"></i><span>{{ __('messages.nav_shop') }}</span></a>
        <a href="{{ route('member.profile') }}"><i class="bi bi-person"></i><span>{{ __('messages.nav_profile') }}</span></a>
    </div>
    <h2 class="member-section"><i class="bi bi-hexagon"></i> {{ __('messages.pv_account') }}</h2>
    <div class="member-metrics">
        <div class="member-metric">
            <div class="member-metric-icon is-warn"><i class="bi bi-hourglass-split"></i></div>
            <span>{{ __('messages.pv_pending') }}</span>
            <strong>{{ number_format($pvPending, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon"><i class="bi bi-check2-circle"></i></div>
            <span>{{ __('messages.pv_confirmed') }}</span>
            <strong>{{ number_format($pvConfirmed, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon"><i class="bi bi-arrow-down-left"></i></div>
            <span>{{ __('messages.left_leg_pv') }}</span>
            <strong>{{ number_format($leftLegPv, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon"><i class="bi bi-arrow-down-right"></i></div>
            <span>{{ __('messages.right_leg_pv') }}</span>
            <strong>{{ number_format($rightLegPv, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon is-gold"><i class="bi bi-activity"></i></div>
            <span>{{ __('messages.weak_leg_pv') }}</span>
            <strong>{{ number_format($weakLegPv, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon"><i class="bi bi-stars"></i></div>
            <span>{{ __('messages.pv_total') }}</span>
            <strong>{{ number_format($pvTotal, 2) }}</strong>
        </div>
    </div>
    <h2 class="member-section"><i class="bi bi-wallet2"></i> {{ __('messages.account_balance') }}</h2>
    <div class="member-metrics">
        <div class="member-metric">
            <div class="member-metric-icon is-warn"><i class="bi bi-clock"></i></div>
            <span>{{ __('messages.gains_pending') }}</span>
            <strong>{{ number_format($gainsPending, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon"><i class="bi bi-cash-stack"></i></div>
            <span>{{ __('messages.gains_available') }}</span>
            <strong>{{ number_format($payoutAvailable, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon"><i class="bi bi-check-lg"></i></div>
            <span>{{ __('messages.gains_paid') }}</span>
            <strong>{{ number_format($gainsPaid, 2) }}</strong>
        </div>
        <div class="member-metric">
            <div class="member-metric-icon is-gold"><i class="bi bi-graph-up-arrow"></i></div>
            <span>{{ __('messages.gains_total') }}</span>
            <strong>{{ number_format($gainsTotal, 2) }}</strong>
        </div>
    </div>
    <section class="member-sheet">
        <h2><i class="bi bi-cash-coin"></i> {{ __('messages.payout_request') }}</h2>
        <p class="text-muted small">{{ __('messages.payout_request_hint') }}</p>
        @if($pendingPayout)
            <div class="alert alert-warning mb-0">
                {{ __('messages.payout_pending_notice', ['amount' => number_format($pendingPayout->amount_usd, 2)]) }}
            </div>
        @elseif($payoutAvailable > 0)
            <form method="post" action="{{ route('member.payout.request') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-6 col-md-4">
                    <label class="form-label" for="payout-amount">{{ __('messages.amount') }} (USD)</label>
                    <input class="form-control" id="payout-amount" type="number" step="0.01" min="0.01" max="{{ $payoutAvailable }}" name="amount" value="{{ old('amount', number_format($payoutAvailable, 2, '.', '')) }}" required>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="payout-note">{{ __('messages.note') }}</label>
                    <input class="form-control" id="payout-note" type="text" name="note" maxlength="500" value="{{ old('note') }}" placeholder="{{ __('messages.payout_note_placeholder') }}">
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-success w-100" type="submit">{{ __('messages.payout_submit') }}</button>
                </div>
            </form>
        @else
            <p class="text-muted mb-0">{{ __('messages.payout_no_balance') }}</p>
        @endif
        @if($recentPayouts->isNotEmpty())
            <div class="mt-3">
                @foreach($recentPayouts as $row)
                    <div class="member-row">
                        <div class="member-row-main">
                            <i class="bi bi-cash"></i>
                            <div>
                                <strong>{{ number_format($row->amount_usd, 2) }} USD</strong>
                                <div class="text-muted">{{ $row->created_at?->format('Y-m-d') }} · {{ __('messages.payout_status_'.$row->status) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
    <section class="member-sheet">
        <h2><i class="bi bi-people"></i> {{ __('messages.referred_clients') }}</h2>
        <p class="text-muted small">{{ __('messages.client_pv_held_hint') }}</p>
        @forelse($orientedClients as $client)
            <div class="member-row">
                <div class="member-row-main">
                    <i class="bi bi-person"></i>
                    <span>{{ $client->name }}</span>
                </div>
                <span>{{ number_format((float) $client->accumulated_pv, 2) }} / {{ number_format($membershipThreshold, 2) }} PV
                    @if($client->converted_member_id)
                        · {{ __('messages.membership_indirect') }}
                    @elseif((float) $client->accumulated_pv >= $membershipThreshold)
                        · {{ __('messages.threshold_reached') }}
                    @endif
                </span>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
        @endforelse
    </section>
    <section class="member-sheet">
        <h2><i class="bi bi-trophy"></i> {{ __('messages.prizes') }}</h2>
        <p class="text-muted small">{{ __('messages.prizes_hint') }}</p>
        <div class="prize-grid">
            @forelse($prizes as $prize)
                @php
                    $tier = $prize['tier'];
                    $needed = (float) $tier->min_pv;
                    $progress = $needed > 0 ? min(100, ($weakLegPv / $needed) * 100) : 0;
                @endphp
                <article class="prize-card is-{{ $prize['state'] }}">
                    <div class="prize-card-photo">
                        @if($tier->imageUrl())
                            <img src="{{ $tier->imageUrl() }}" alt="{{ $tier->label }}">
                        @else
                            <i class="bi bi-gift"></i>
                        @endif
                    </div>
                    <div class="prize-card-body">
                        <strong>{{ $tier->label }}</strong>
                        <div class="text-muted">{{ __('messages.min_weak_leg_pv') }}: {{ number_format($needed, 0) }}</div>
                        <div class="prize-progress"><span style="width: {{ $progress }}%"></span></div>
                        <span class="badge rounded-pill {{ $prize['state'] === 'served' ? 'text-bg-success' : ($prize['state'] === 'pending' ? 'text-bg-warning' : 'text-bg-secondary') }}">
                            {{ __('messages.prize_'.$prize['state']) }}
                        </span>
                    </div>
                </article>
            @empty
                <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
            @endforelse
        </div>
        @if($nextTier)
            <p class="member-hint mb-0 mt-3"><i class="bi bi-flag"></i> {{ __('messages.next_tier', ['label' => $nextTier->label, 'pv' => number_format((float) $nextTier->min_pv, 0)]) }}</p>
        @endif
    </section>
    <section class="member-sheet">
        <div class="member-sheet-head">
            <h2><i class="bi bi-activity"></i> {{ __('messages.pv_history') }}</h2>
            <a href="{{ route('member.history') }}">{{ __('messages.see_all') }}</a>
        </div>
        @forelse($recentPv as $entry)
            <div class="member-row">
                <div class="member-row-main">
                    <i class="bi bi-hexagon"></i>
                    <span>{{ __('messages.'.$entry->source_type) }}</span>
                </div>
                <span>{{ number_format($entry->pv_amount, 2) }} · {{ __('messages.'.$entry->sync_status) }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
        @endforelse
    </section>
    <section class="member-sheet">
        <div class="member-sheet-head">
            <h2><i class="bi bi-currency-dollar"></i> {{ __('messages.commission_history') }}</h2>
            <a href="{{ route('member.history') }}">{{ __('messages.see_all') }}</a>
        </div>
        @forelse($recentCommissions as $row)
            <div class="member-row">
                <div class="member-row-main">
                    <i class="bi bi-gift"></i>
                    <span>{{ \App\Services\ReportBuilder::commissionLabel($row) }}</span>
                </div>
                <span>{{ number_format($row->amount_usd, 2) }} USD · {{ __('messages.'.$row->status) }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
        @endforelse
    </section>
@endsection
