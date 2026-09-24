@extends('layouts.staff')
@section('title', __('messages.commissions'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.commissions') }}</h1>
        <a class="btn btn-outline-success" href="{{ route('admin.reports.print', ['type' => 'commissions_unpaid']) }}">{{ __('messages.print_a4') }}</a>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.members') }}</th>
                            <th>{{ __('messages.type') }}</th>
                            <th>{{ __('messages.generation') }}</th>
                            <th>{{ __('messages.related_member') }}</th>
                            <th>{{ __('messages.amount_usd') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                            <tr>
                                <td>{{ $commission->member?->full_name }}</td>
                                <td>{{ \App\Services\ReportBuilder::commissionLabel($commission) }}</td>
                                <td>{{ $commission->type === 'equilibrium' && $commission->generation ? $commission->generation : '—' }}</td>
                                <td>{{ $commission->relatedMember?->full_name ?? '—' }}</td>
                                <td>{{ number_format($commission->amount_usd, 2) }}</td>
                                <td>{{ __('messages.'.$commission->status) }}</td>
                                <td class="text-nowrap">
                                    @if($commission->status !== 'paid')
                                        <form method="post" action="{{ route('admin.commissions.pay', $commission) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">{{ __('messages.pay') }}</button>
                                        </form>
                                        @include('partials.delete-form', ['action' => route('admin.commissions.destroy', $commission)])
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $commissions->links() }}</div>
        </div>
    </div>
@endsection
