@extends('layouts.member')
@section('title', __('messages.genealogy'))
@section('content')
    <div class="member-page-head">
        <h1><i class="bi bi-diagram-3"></i> {{ __('messages.genealogy') }}</h1>
        <p>{{ trans_choice('messages.downline_count', $downlineCount, ['count' => $downlineCount]) }}</p>
        <a class="btn btn-outline-success btn-sm" href="{{ route('member.reports.print', 'member_tree') }}">{{ __('messages.print') }}</a>
    </div>
    @if($member->isAwaitingPlacement())
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> {{ __('messages.awaiting_placement') }}</div>
    @endif
    <div class="member-legs">
        <div class="member-leg-card">
            <i class="bi bi-arrow-down-left"></i>
            <span>{{ __('messages.left_leg_pv') }}</span>
            <strong>{{ number_format($leftLegPv, 2) }}</strong>
        </div>
        <div class="member-leg-card">
            <i class="bi bi-arrow-down-right"></i>
            <span>{{ __('messages.right_leg_pv') }}</span>
            <strong>{{ number_format($rightLegPv, 2) }}</strong>
        </div>
    </div>

    @if(isset($trail) && count($trail) > 1)
        <nav class="tree-breadcrumb mb-2" aria-label="{{ __('messages.tree_view_root') }}">
            @foreach($trail as $crumb)
                @if($loop->last)
                    <span class="tree-breadcrumb-current">{{ $crumb->full_name }}</span>
                @else
                    <a href="{{ route('member.network', ['root' => $crumb->id]) }}">{{ $loop->first ? __('messages.tree_back_root') : $crumb->full_name }}</a>
                    <span class="tree-breadcrumb-sep">/</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div
        id="member-tree-detail"
        class="member-tree-detail"
        hidden
        data-label-generation="{{ __('messages.member_tree_generation') }}"
        data-label-you="{{ __('messages.member_tree_you') }}"
        data-label-left="{{ __('messages.left_leg_pv') }}"
        data-label-right="{{ __('messages.right_leg_pv') }}"
        data-label-drill="{{ __('messages.tree_drill_hint') }}"
    >
        <div class="member-tree-detail-head">
            <strong id="member-tree-detail-name"></strong>
            <span id="member-tree-detail-code" class="text-muted"></span>
        </div>
        <div class="member-tree-detail-gen" id="member-tree-detail-gen"></div>
        <div class="member-tree-detail-pv">
            <span id="member-tree-detail-left"></span>
            <span id="member-tree-detail-right"></span>
        </div>
        <ul class="member-tree-detail-list" id="member-tree-detail-path" hidden></ul>
        <a id="member-tree-detail-drill" class="btn btn-sm btn-success mt-2" hidden href="#">{{ __('messages.tree_drill_hint') }}</a>
    </div>

    <p class="member-hint"><i class="bi bi-hand-index"></i> {{ __('messages.member_tree_tap_hint') }}</p>
    <div class="tree-phone-toolbar">
        <button type="button" class="btn btn-outline-success btn-sm" data-tree-zoom="out">−</button>
        <button type="button" class="btn btn-outline-success btn-sm" data-tree-zoom="reset">{{ __('messages.tree_zoom_reset') }}</button>
        <button type="button" class="btn btn-outline-success btn-sm" data-tree-zoom="in">+</button>
    </div>
    <div
        class="mlm-tree-scroll tree-phone"
        data-tree-mode="member"
        data-root-id="{{ $treeRoot->id }}"
        data-eq-near="{{ $eqAmounts['near'] }}"
        data-eq-after="{{ $eqAmounts['after'] }}"
    >
        <ul class="mlm-tree">
            @include('admin.members._nodes', [
                'nodes' => collect([$treeRoot]),
                'grouped' => $grouped,
                'highlightId' => $treeRoot->id,
                'subtreePv' => $subtreePv,
                'eqLevels' => [],
                'eqAmounts' => $eqAmounts,
                'generation' => 0,
                'compact' => true,
                'maxDepth' => $maxDepth ?? 4,
                'drillBase' => $drillBase ?? route('member.network'),
            ])
        </ul>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/member-tree.js')
@endpush
