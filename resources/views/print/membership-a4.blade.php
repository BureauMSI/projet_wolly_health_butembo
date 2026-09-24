@extends('layouts.print')
@section('title', __('messages.membership_sheet'))
@section('print_css')
<style>
    body { width: 190mm; font-size: 13px; padding: 12mm; }
    @page { size: A4; margin: 12mm; }
    .box { border: 1px solid #333; padding: 8px; margin-top: 8px; }
</style>
@endsection
@section('content')
    <div class="center">
        <h1>{{ $institution->name ?? __('messages.name') }}</h1>
        <p>{{ __('messages.membership_sheet') }}</p>
    </div>
    <div class="box">
        <p><strong>{{ __('messages.member_code') }}:</strong> {{ $member->member_code }}</p>
        <p><strong>{{ __('messages.full_name') }}:</strong> {{ $member->full_name }}</p>
        <p><strong>{{ __('messages.username') }}:</strong> {{ $member->username }}</p>
        <p><strong>{{ __('messages.phone') }}:</strong> {{ $member->phone }}</p>
        <p><strong>{{ __('messages.sponsor') }}:</strong> {{ $member->sponsor?->full_name ?? '—' }}</p>
        <p><strong>{{ __('messages.placement') }}:</strong>
            @if($member->placementParent)
                {{ $member->placementParent->full_name }} ({{ __('messages.'.$member->placement_side) }})
            @elseif($member->isTreeRoot())
                {{ __('messages.root_member') }}
            @else
                {{ __('messages.awaiting_placement') }}
            @endif
        </p>
        <p><strong>{{ __('messages.joined_at') }}:</strong> {{ $member->joined_at?->format('Y-m-d') }}</p>
        <p><strong>{{ __('messages.membership_type') }}:</strong> {{ __('messages.membership_'.($member->membership_type ?: 'direct')) }}</p>
        <p><strong>{{ __('messages.membership_amount') }}:</strong> {{ number_format((float) $member->membership_amount_usd, 2) }} USD</p>
        <p><strong>{{ __('messages.membership_pv') }}:</strong> {{ number_format((float) $member->membership_pv, 2) }}</p>
        @if($member->sourceClient)
            <p><strong>{{ __('messages.source_client') }}:</strong> {{ $member->sourceClient->name }}</p>
        @endif
        <p><strong>{{ __('messages.registration_branch') }}:</strong> {{ $member->registrationBranch?->name }}</p>
    </div>
@endsection
