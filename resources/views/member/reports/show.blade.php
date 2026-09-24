@extends('layouts.member')
@section('title', $title)
@section('content')
    @include('print._magolio-styles')
    <style>
        .rapport-feuille { background: #fff; border: 1px solid #ccc; padding: 16px 18px; box-shadow: 0 0.35rem 1.1rem rgba(14, 61, 40, 0.06); overflow-x: auto; }
    </style>
    <div class="no-print mb-3">
        <div class="member-page-head mb-2">
            <h1>{{ $title }}</h1>
            <p>{{ __('messages.report_'.$type.'_hint') }}</p>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('member.reports.index') }}">{{ __('messages.back') }}</a>
                <a class="btn btn-primary" href="{{ route('member.reports.print', array_merge(request()->query(), ['type' => $type])) }}">{{ __('messages.print') }}</a>
            </div>
        </div>
        @if(! in_array($type, ['member_tree', 'member_sheet'], true))
            @include('member.reports._filters')
        @endif
    </div>
    <div class="rapport-officiel rapport-feuille">
        @include('print._letterhead')
        @include('print.reports.'.$type)
        @include('print._signatures', ['printedAt' => now(), 'printedBy' => auth('member')->user()->full_name])
    </div>
@endsection
