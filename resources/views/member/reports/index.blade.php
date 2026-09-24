@extends('layouts.member')
@section('title', __('messages.reports'))
@section('content')
    <div class="member-page-head">
        <h1><i class="bi bi-printer"></i> {{ __('messages.reports') }}</h1>
        <p>{{ __('messages.report_member_catalog_intro') }}</p>
    </div>
    <div class="row g-3">
        @foreach($items as $item)
            <div class="col-md-6">
                <a class="text-decoration-none" href="{{ route('member.reports.show', $item['type']) }}">
                    <div class="member-sheet h-100">
                        <div class="d-flex gap-3 align-items-start">
                            <i class="bi {{ $item['icon'] }} fs-3 text-success"></i>
                            <div>
                                <div class="fw-semibold text-dark">{{ __('messages.report_'.$item['type']) }}</div>
                                <div class="small text-muted">{{ __('messages.report_'.$item['type'].'_hint') }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
