@extends('layouts.member')
@section('title', __('messages.history'))
@section('content')
    <div class="member-page-head">
        <h1><i class="bi bi-clock-history"></i> {{ __('messages.history') }}</h1>
        <p>{{ __('messages.offline_note') }}</p>
        <a class="btn btn-outline-success btn-sm" href="{{ route('member.reports.show', 'member_statement') }}">{{ __('messages.reports') }}</a>
    </div>
    <section class="member-sheet">
        <h2><i class="bi bi-hexagon"></i> {{ __('messages.pv_history') }}</h2>
        @forelse($pvEntries as $entry)
            <div class="member-row">
                <div class="member-row-main">
                    <i class="bi bi-plus-circle"></i>
                    <div>
                        <strong>{{ __('messages.'.$entry->source_type) }}</strong>
                        <div class="text-muted">{{ $entry->occurred_at?->format('Y-m-d') }} · {{ __('messages.'.$entry->sync_status) }}</div>
                    </div>
                </div>
                <span>{{ number_format($entry->pv_amount, 2) }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
        @endforelse
        <div class="mt-2">{{ $pvEntries->links() }}</div>
    </section>
    <section class="member-sheet">
        <h2><i class="bi bi-wallet2"></i> {{ __('messages.commission_history') }}</h2>
        @forelse($commissions as $row)
            <div class="member-row">
                <div class="member-row-main">
                    <i class="bi bi-cash-coin"></i>
                    <div>
                        <strong>{{ \App\Services\ReportBuilder::commissionLabel($row) }}</strong>
                        <div class="text-muted">
                            {{ $row->occurred_at?->format('Y-m-d') }}
                            @if($row->type === 'equilibrium' && $row->generation)
                                · {{ __('messages.generation') }} {{ $row->generation }}
                            @endif
                            @if($row->relatedMember) · {{ $row->relatedMember->full_name }} @endif
                        </div>
                    </div>
                </div>
                <span>{{ number_format($row->amount_usd, 2) }} USD · {{ __('messages.'.$row->status) }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
        @endforelse
        <div class="mt-2">{{ $commissions->links() }}</div>
    </section>
@endsection
