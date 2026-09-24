<div class="kpi">
    <div><strong>{{ __('messages.pv') }} :</strong> {{ number_format($pvTotal, 2) }}</div>
    <div><strong>{{ __('messages.left_leg_pv') }} :</strong> {{ number_format($leftLegPv, 2) }}</div>
    <div><strong>{{ __('messages.right_leg_pv') }} :</strong> {{ number_format($rightLegPv, 2) }}</div>
    <div><strong>{{ __('messages.weak_leg_pv') }} :</strong> {{ number_format($weakLegPv, 2) }}</div>
    <div><strong>{{ __('messages.gains_unpaid') }} :</strong> {{ number_format($gainsPending + $gainsConfirmed, 2) }} USD</div>
    <div><strong>{{ __('messages.gains_paid') }} :</strong> {{ number_format($gainsPaid, 2) }} USD</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.occurred_at') }}</th>
            <th>{{ __('messages.type') }}</th>
            <th>{{ __('messages.status') }}</th>
            <th class="num">{{ __('messages.pv') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pvEntries as $entry)
            <tr>
                <td>{{ $entry->occurred_at?->format('d/m/Y') }}</td>
                <td class="text-start">{{ __('messages.'.$entry->source_type) }}</td>
                <td>{{ __('messages.'.$entry->sync_status) }}</td>
                <td class="num">{{ number_format($entry->pv_amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('messages.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.occurred_at') }}</th>
            <th>{{ __('messages.type') }}</th>
            <th>{{ __('messages.generation') }}</th>
            <th>{{ __('messages.related_member') }}</th>
            <th>{{ __('messages.status') }}</th>
            <th class="num">{{ __('messages.amount_usd') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($commissions as $commission)
            <tr>
                <td>{{ $commission->occurred_at?->format('d/m/Y') }}</td>
                <td class="text-start">{{ \App\Services\ReportBuilder::commissionLabel($commission) }}</td>
                <td>{{ $commission->type === 'equilibrium' && $commission->generation ? $commission->generation : '—' }}</td>
                <td class="text-start">{{ $commission->relatedMember?->full_name ?? '—' }}</td>
                <td>{{ __('messages.'.$commission->status) }}</td>
                <td class="num">{{ number_format($commission->amount_usd, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">{{ __('messages.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table>
@if(($prizes ?? collect())->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.reward_tiers') }}</th>
            <th class="num">{{ __('messages.min_pv') }}</th>
            <th>{{ __('messages.status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($prizes as $prize)
            <tr>
                <td class="text-start">{{ $prize['tier']->label }}</td>
                <td class="num">{{ number_format((float) $prize['tier']->min_pv, 2) }}</td>
                <td>{{ __('messages.prize_'.$prize['state']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
