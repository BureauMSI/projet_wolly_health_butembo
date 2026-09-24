@extends('layouts.staff')
@section('title', $member->full_name)
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $member->full_name }}</h1>
            <p class="text-muted mb-0">{{ $member->member_code }} · {{ $member->username }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-success" href="{{ route('admin.members.print', $member) }}">{{ __('messages.membership_sheet') }}</a>
            @if($member->phone)
                <form method="post" action="{{ route('admin.members.whatsapp', $member) }}">
                    @csrf
                    <button class="btn btn-outline-success" type="submit">{{ __('messages.whatsapp') }}</button>
                </form>
            @endif
            @can('update', $member)
                <a class="btn btn-outline-success" href="{{ route('admin.members.edit', $member) }}">{{ __('messages.edit') }}</a>
            @endcan
            @can('delete', $member)
                @include('partials.delete-form', ['action' => route('admin.members.destroy', $member), 'class' => 'btn btn-outline-danger'])
            @endcan
        </div>
    </div>
    @if(session('generated_username'))
        <div class="alert alert-info">
            {{ __('messages.generated_username') }}: <strong>{{ session('generated_username') }}</strong>
            @if(session('generated_password'))
                · {{ __('messages.password') }}: <strong>{{ session('generated_password') }}</strong>
            @endif
        </div>
    @endif
    @if(session('prompt_placement'))
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>{{ __('messages.placement_after_registration') }}</span>
            @can('update', $member)
                <a class="btn btn-sm btn-success" href="{{ route('admin.placements.index', ['member' => $member->id]) }}#member-{{ $member->id }}">{{ __('messages.go_to_assignment') }}</a>
            @endcan
        </div>
    @endif
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card surface-card mb-3">
                <div class="card-body">
                    <div class="small text-muted">{{ __('messages.pv_pending') }}</div>
                    <div class="h4">{{ number_format($pvPending, 2) }}</div>
                    <div class="small text-muted mt-2">{{ __('messages.pv_confirmed') }}</div>
                    <div class="h4 mb-0">{{ number_format($pvConfirmed, 2) }}</div>
                    <p class="small text-muted mt-2 mb-0">{{ __('messages.offline_note') }}</p>
                </div>
            </div>
            <div class="card surface-card">
                <div class="card-body">
                    <p><strong>{{ __('messages.sponsor') }}:</strong> {{ $member->sponsor?->full_name ?? '—' }}</p>
                    <p><strong>{{ __('messages.placement') }}:</strong>
                        @include('partials.member-placement', ['member' => $member])
                    </p>
                    <p class="mb-0"><strong>{{ __('messages.phone') }}:</strong> {{ $member->phone ?: '—' }}</p>
                    <p class="mt-2 mb-0"><strong>{{ __('messages.membership_type') }}:</strong> {{ __('messages.membership_'.($member->membership_type ?: 'direct')) }}</p>
                    <p class="mt-2 mb-0"><strong>{{ __('messages.membership_amount') }}:</strong> {{ number_format((float) $member->membership_amount_usd, 2) }} USD</p>
                    <p class="mb-0"><strong>{{ __('messages.membership_pv') }}:</strong> {{ number_format((float) $member->membership_pv, 2) }}</p>
                    @if($member->sourceClient)
                        <p class="mb-0"><strong>{{ __('messages.source_client') }}:</strong> {{ $member->sourceClient->name }} ({{ __('messages.referrer') }}: {{ $member->sourceClient->referrer?->full_name }})</p>
                    @endif
                </div>
            </div>
            @if($member->isAwaitingPlacement())
                @can('update', $member)
                    <a class="btn btn-success w-100 mt-3" href="{{ route('admin.placements.index', ['member' => $member->id]) }}#member-{{ $member->id }}">{{ __('messages.go_to_assignment') }}</a>
                @endcan
            @endif
        </div>
        <div class="col-lg-8">
            <div class="card surface-card mb-3">
                <div class="card-body">
                    <h2 class="h6">{{ __('messages.pv') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>{{ __('messages.source') }}</th><th>{{ __('messages.pv') }}</th><th>{{ __('messages.sync_status') }}</th></tr></thead>
                            <tbody>
                                @forelse($pvEntries as $entry)
                                    <tr>
                                        <td>{{ __('messages.'.$entry->source_type) }}</td>
                                        <td>{{ number_format($entry->pv_amount, 2) }}</td>
                                        <td>{{ __('messages.'.$entry->sync_status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $pvEntries->links() }}</div>
                </div>
            </div>
            <div class="card surface-card">
                <div class="card-body">
                    <h2 class="h6">{{ __('messages.commissions') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.type') }}</th>
                                    <th>{{ __('messages.generation') }}</th>
                                    <th>{{ __('messages.related_member') }}</th>
                                    <th>{{ __('messages.amount_usd') }}</th>
                                    <th>{{ __('messages.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissions as $row)
                                    <tr>
                                        <td>{{ \App\Services\ReportBuilder::commissionLabel($row) }}</td>
                                        <td>{{ $row->type === 'equilibrium' && $row->generation ? $row->generation : '—' }}</td>
                                        <td>{{ $row->relatedMember?->full_name ?? '—' }}</td>
                                        <td>{{ number_format($row->amount_usd, 2) }}</td>
                                        <td>{{ __('messages.'.$row->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $commissions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
