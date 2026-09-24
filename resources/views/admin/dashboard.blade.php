@extends('layouts.staff')

@section('title', __('messages.dashboard'))

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ __('messages.dashboard') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.dashboard_welcome') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($pendingPayoutCount > 0)
                <a class="btn btn-warning" href="{{ route('admin.payouts.index') }}">
                    {{ __('messages.payout_alerts_btn', ['count' => $pendingPayoutCount]) }}
                </a>
            @endif
            @can('create', App\Models\Sale::class)
                <a class="btn btn-success" href="{{ route('admin.sales.create') }}">{{ __('messages.new_sale') }}</a>
            @endcan
        </div>
    </div>

    @if($pendingPayouts->isNotEmpty())
        <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <strong>{{ __('messages.payout_alerts_title') }}</strong>
                <div class="small mb-0">{{ __('messages.payout_alerts_hint') }}</div>
            </div>
            <ul class="mb-0 small">
                @foreach($pendingPayouts->take(3) as $payout)
                    <li>
                        {{ $payout->member?->full_name }} — {{ number_format($payout->amount_usd, 2) }} USD
                        <span class="text-muted">({{ $payout->created_at?->format('d/m H:i') }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ __('messages.member_count') }}</div>
                    <div class="stat-value">{{ $memberCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ __('messages.today_sales') }}</div>
                    <div class="stat-value">{{ $salesToday }}</div>
                    <div class="small text-muted">{{ number_format($salesTodayUsd, 2) }} USD</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ __('messages.today_cash_in') }}</div>
                    <div class="stat-value text-success">{{ number_format($cashInToday, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ __('messages.today_cash_out') }}</div>
                    <div class="stat-value text-danger">{{ number_format($cashOutToday, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ __('messages.cash_balance') }}</div>
                    <div class="stat-value">{{ number_format($cashBalance, 2) }}</div>
                    <div class="small text-muted">USD</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">{{ __('messages.sales_trend') }}</h2>
                <span class="small text-muted">{{ __('messages.sales_trend_hint') }}</span>
            </div>
            <div class="dash-trend">
                @foreach($salesTrend as $day)
                    @php $height = (int) round(($day['total'] / $salesTrendMax) * 100); @endphp
                    <div class="dash-trend-col" title="{{ $day['label'] }}: {{ number_format($day['total'], 2) }} USD ({{ $day['count'] }})">
                        <div class="dash-trend-bar-wrap">
                            <div class="dash-trend-bar" style="height: {{ max($height, $day['total'] > 0 ? 8 : 2) }}%"></div>
                        </div>
                        <div class="dash-trend-label">{{ $day['label'] }}</div>
                        <div class="dash-trend-value">{{ number_format($day['total'], 0) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card surface-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h2 class="h6 mb-0">{{ __('messages.recent_sales') }}</h2>
                        <a class="small" href="{{ route('admin.sales.index') }}">{{ __('messages.see_all') }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.sale_number') }}</th>
                                    <th>{{ __('messages.buyer') }}</th>
                                    <th>{{ __('messages.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td><a href="{{ route('admin.sales.show', $sale) }}">{{ $sale->number }}</a></td>
                                        <td>{{ $sale->buyerName() }}</td>
                                        <td>{{ number_format($sale->total_usd, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card surface-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h2 class="h6 mb-0">{{ __('messages.recent_members') }}</h2>
                        <a class="small" href="{{ route('admin.members.index') }}">{{ __('messages.see_all') }}</a>
                    </div>
                    <ul class="list-unstyled mb-0">
                        @forelse($recentMembers as $member)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <a href="{{ route('admin.members.show', $member) }}">{{ $member->full_name }}</a>
                                <span class="text-muted small">{{ $member->member_code }}</span>
                            </li>
                        @empty
                            <li class="text-muted">{{ __('messages.no_records') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card surface-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h2 class="h6 mb-0">{{ __('messages.recent_movements') }}</h2>
                        <a class="small" href="{{ route('admin.operations.index') }}">{{ __('messages.see_all') }}</a>
                    </div>
                    <ul class="list-unstyled mb-0">
                        @forelse($recentMovements as $movement)
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $movement->categoryLabel() }}</div>
                                    <div class="small text-muted">{{ $movement->occurred_at?->format('d/m H:i') }}</div>
                                </div>
                                <span class="{{ $movement->direction === 'in' ? 'text-success' : 'text-danger' }} text-nowrap">
                                    {{ $movement->direction === 'in' ? '+' : '-' }}{{ number_format($movement->amount_usd, 2) }}
                                </span>
                            </li>
                        @empty
                            <li class="text-muted">{{ __('messages.no_records') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if($pendingPayouts->isNotEmpty())
        <div class="card surface-card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h2 class="h6 mb-0">{{ __('messages.payout_requests') }}</h2>
                    <a class="small" href="{{ route('admin.payouts.index') }}">{{ __('messages.see_all') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.member') }}</th>
                                <th>{{ __('messages.amount') }}</th>
                                <th>{{ __('messages.occurred_at') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingPayouts as $payout)
                                <tr>
                                    <td>
                                        {{ $payout->member?->full_name }}
                                        <div class="small text-muted">{{ $payout->member?->member_code }} · {{ $payout->member?->phone }}</div>
                                    </td>
                                    <td>{{ number_format($payout->amount_usd, 2) }} USD</td>
                                    <td>{{ $payout->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.payouts.index') }}">{{ __('messages.review') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
