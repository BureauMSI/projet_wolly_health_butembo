@extends('layouts.staff')
@section('title', __('messages.reward_deliveries'))
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ __('messages.reward_deliveries') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.reward_deliveries_hint') }}</p>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-sm {{ $status === 'pending' ? 'btn-success' : 'btn-outline-success' }}" href="{{ route('admin.rewards.index', ['status' => 'pending']) }}">{{ __('messages.reward_filter_pending') }}</a>
        <a class="btn btn-sm {{ $status === 'served' ? 'btn-success' : 'btn-outline-success' }}" href="{{ route('admin.rewards.index', ['status' => 'served']) }}">{{ __('messages.reward_filter_served') }}</a>
        <a class="btn btn-sm {{ $status === 'all' ? 'btn-success' : 'btn-outline-success' }}" href="{{ route('admin.rewards.index', ['status' => 'all']) }}">{{ __('messages.reward_filter_all') }}</a>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.member') }}</th>
                            <th>{{ __('messages.reward_object') }}</th>
                            <th>{{ __('messages.min_weak_leg_pv') }}</th>
                            <th>{{ __('messages.occurred_at') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rewards as $reward)
                            @php
                                $tier = $reward->rewardTier;
                                $served = $reward->status === 'paid';
                            @endphp
                            <tr class="{{ $served ? '' : 'table-warning' }}">
                                <td>
                                    @if($reward->member)
                                        <a href="{{ route('admin.members.show', $reward->member) }}">{{ $reward->member->full_name }}</a>
                                        <div class="small text-muted">{{ $reward->member->member_code }} · {{ $reward->member->phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="prize-card-photo">
                                            @if($tier?->imageUrl())
                                                <img src="{{ $tier->imageUrl() }}" alt="{{ $tier->label }}">
                                            @else
                                                <i class="bi bi-gift"></i>
                                            @endif
                                        </div>
                                        <strong>{{ $tier?->label ?? __('messages.reward') }}</strong>
                                    </div>
                                </td>
                                <td>{{ $tier ? number_format((float) $tier->min_pv, 0) : '—' }}</td>
                                <td>{{ $reward->occurred_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $served ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ __('messages.prize_'.($served ? 'served' : 'pending')) }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    @if(! $served && (auth()->user()->canManagePayouts() || auth()->user()->isCashier()))
                                        <form method="post" action="{{ route('admin.rewards.serve', $reward) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">{{ __('messages.reward_serve') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $rewards->links() }}</div>
        </div>
    </div>
@endsection
