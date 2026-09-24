@extends('layouts.staff')
@section('title', __('messages.tree'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.tree') }}</h1>
        <a class="btn btn-primary" href="{{ route('admin.reports.print', 'tree') }}">{{ __('messages.print') }}</a>
    </div>
    @if($unplaced->isNotEmpty())
        <div class="card surface-card mb-3">
            <div class="card-body">
                <h2 class="h6">{{ __('messages.awaiting_placement') }}</h2>
                <ul class="list-unstyled mb-0">
                    @foreach($unplaced as $member)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span>{{ $member->full_name }} · {{ $member->username }}</span>
                            <a class="btn btn-sm btn-success" href="{{ route('admin.placements.index', ['member' => $member->id]) }}#member-{{ $member->id }}">{{ __('messages.go_to_assignment') }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    <div class="card surface-card mb-3">
        <div class="card-body py-3">
            <p class="mb-2 small text-muted">{{ __('messages.tree_depth_hint') }}</p>
            <p class="mb-2 small text-muted d-lg-none">{{ __('messages.tree_phone_hint') }}</p>
            @if(isset($trail) && count($trail) > 1)
                <nav class="tree-breadcrumb mb-2" aria-label="{{ __('messages.tree_view_root') }}">
                    @foreach($trail as $crumb)
                        @if($loop->last)
                            <span class="tree-breadcrumb-current">{{ $crumb->full_name }}</span>
                        @else
                            <a href="{{ route('admin.members.tree', ['root' => $crumb->id] + ($focusMember ? ['member' => $focusMember->id] : [])) }}">{{ $loop->first ? __('messages.tree_back_root') : $crumb->full_name }}</a>
                            <span class="tree-breadcrumb-sep">/</span>
                        @endif
                    @endforeach
                </nav>
            @endif
            <div class="d-flex flex-wrap gap-3 align-items-center small">
                <span class="mlm-eq-legend mlm-eq-legend-near">
                    {{ __('messages.generations_1_4') }} · {{ number_format($eqAmounts['near'], 0) }}$
                </span>
                <span class="mlm-eq-legend mlm-eq-legend-far">
                    {{ __('messages.after_generation_4') }} · {{ number_format($eqAmounts['after'], 0) }}$
                </span>
                @if($focusMember)
                    <span class="text-muted">
                        {{ __('messages.tree_equilibrium_focus', ['name' => $focusMember->full_name]) }}
                        · <a href="{{ route('admin.members.tree', array_filter(['root' => $viewRoot?->id])) }}">{{ __('messages.clear_selection') }}</a>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div
        id="member-tree-detail"
        class="member-tree-detail"
        hidden
        data-label-generation="{{ __('messages.member_tree_generation') }}"
        data-label-you="{{ __('messages.member_tree_you') }}"
        data-label-left="{{ __('messages.left_leg_pv') }}"
        data-label-right="{{ __('messages.right_leg_pv') }}"
        data-label-path="{{ __('messages.tree_equilibrium_path') }}"
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

    <div class="tree-phone-toolbar">
        <button type="button" class="btn btn-outline-success btn-sm" data-tree-zoom="out">−</button>
        <button type="button" class="btn btn-outline-success btn-sm" data-tree-zoom="reset">{{ __('messages.tree_zoom_reset') }}</button>
        <button type="button" class="btn btn-outline-success btn-sm" data-tree-zoom="in">+</button>
    </div>

    <div class="card surface-card">
        <div
            class="mlm-tree-scroll tree-phone"
            data-tree-mode="admin"
            data-root-id="{{ $viewRoot?->id }}"
            data-eq-near="{{ $eqAmounts['near'] }}"
            data-eq-after="{{ $eqAmounts['after'] }}"
            data-eq-label="{{ __('messages.equilibrium_n', ['n' => '__N__']) }}"
            data-eq-source-label="{{ __('messages.tree_equilibrium_source') }}"
        >
            @if($roots->isEmpty())
                <p class="text-muted mb-0">{{ __('messages.no_records') }}</p>
            @else
                <ul class="mlm-tree">
                    @include('admin.members._nodes', [
                        'nodes' => $roots,
                        'grouped' => $grouped,
                        'subtreePv' => $subtreePv,
                        'highlightId' => $highlightId ?? null,
                        'eqLevels' => $eqLevels ?? [],
                        'eqAmounts' => $eqAmounts,
                        'compact' => true,
                        'maxDepth' => $maxDepth ?? 4,
                        'drillBase' => $drillBase ?? route('admin.members.tree'),
                        'generation' => 0,
                    ])
                </ul>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/member-tree.js')
@endpush
