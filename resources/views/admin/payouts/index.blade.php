@extends('layouts.staff')
@section('title', __('messages.payout_requests'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.payout_requests') }}</h1>
        <a class="btn btn-outline-success" href="{{ route('admin.dashboard') }}">{{ __('messages.back') }}</a>
    </div>
    <p class="text-muted">{{ __('messages.payout_requests_hint') }}</p>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.member') }}</th>
                            <th>{{ __('messages.amount') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th>{{ __('messages.note') }}</th>
                            <th>{{ __('messages.occurred_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $row)
                            <tr class="{{ $row->status === 'pending' ? 'table-warning' : '' }}">
                                <td>
                                    @if($row->member)
                                        <a href="{{ route('admin.members.show', $row->member) }}">{{ $row->member->full_name }}</a>
                                        <div class="small text-muted">{{ $row->member->member_code }} · {{ $row->member->phone }}</div>
                                    @endif
                                </td>
                                <td>{{ number_format($row->amount_usd, 2) }} USD</td>
                                <td>{{ __('messages.payout_status_'.$row->status) }}</td>
                                <td class="small">{{ $row->note ?: '—' }}</td>
                                <td>{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end text-nowrap">
                                    @if($row->status === 'pending' && auth()->user()->canManagePayouts())
                                        <form method="post" action="{{ route('admin.payouts.pay', $row) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">{{ __('messages.payout_pay') }}</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.payouts.reject', $row) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('messages.payout_reject') }}</button>
                                        </form>
                                    @elseif($row->reviewer)
                                        <span class="small text-muted">{{ $row->reviewer->name }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $requests->links() }}</div>
        </div>
    </div>
@endsection
