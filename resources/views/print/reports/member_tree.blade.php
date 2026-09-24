<div class="kpi">
    @isset($downlineCount)
        <div><strong>{{ __('messages.downline') }} :</strong> {{ $downlineCount }}</div>
    @endisset
    @isset($leftLegPv)
        <div><strong>{{ __('messages.left_leg_pv') }} :</strong> {{ number_format($leftLegPv, 2) }}</div>
        <div><strong>{{ __('messages.right_leg_pv') }} :</strong> {{ number_format($rightLegPv, 2) }}</div>
    @endisset
</div>
@if($roots->isEmpty())
    <p>{{ __('messages.no_records') }}</p>
@else
    <div class="print-tree-wrap">
        <ul class="mlm-tree">
            @include('admin.members._nodes', ['nodes' => $roots, 'grouped' => $grouped, 'highlightId' => $highlightId ?? null, 'subtreePv' => $subtreePv, 'eqLevels' => $eqLevels ?? [], 'eqAmounts' => $eqAmounts ?? \App\Support\EquilibriumPath::amounts()])
        </ul>
    </div>
@endif
