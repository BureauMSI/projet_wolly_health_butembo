@extends('layouts.member')
@section('title', __('messages.purchases'))
@section('content')
    <div class="member-page-head">
        <h1><i class="bi bi-bag-check"></i> {{ __('messages.purchases') }}</h1>
        <a class="btn btn-outline-success btn-sm" href="{{ route('member.reports.show', 'member_purchases') }}">{{ __('messages.reports') }}</a>
    </div>
    @forelse($sales as $sale)
        <section class="member-sheet">
            <div class="member-row">
                <div class="member-row-main">
                    <i class="bi bi-receipt"></i>
                    <div>
                        <strong>{{ $sale->number }}</strong>
                        <div class="text-muted">{{ $sale->sold_at?->format('Y-m-d') }} · {{ __('messages.'.$sale->sync_status) }}</div>
                    </div>
                </div>
                <span>{{ number_format($sale->total_usd, 2) }} USD</span>
            </div>
        </section>
    @empty
        <p class="text-muted">{{ __('messages.no_records') }}</p>
    @endforelse
    {{ $sales->links() }}
@endsection
