<div class="kpi">
    <div><strong>{{ __('messages.total') }} :</strong> {{ number_format($commissionsTotal, 2) }} USD</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.occurred_at') }}</th>
            <th>{{ __('messages.member_code') }}</th>
            <th>{{ __('messages.members') }}</th>
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
                <td>{{ $commission->member?->member_code }}</td>
                <td class="text-start">{{ $commission->member?->full_name }}</td>
                <td class="text-start">{{ \App\Services\ReportBuilder::commissionLabel($commission) }}</td>
                <td>{{ $commission->type === 'equilibrium' && $commission->generation ? $commission->generation : '—' }}</td>
                <td class="text-start">{{ $commission->relatedMember?->full_name ?? '—' }}</td>
                <td>{{ __('messages.'.$commission->status) }}</td>
                <td class="num">{{ number_format($commission->amount_usd, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">{{ __('messages.no_records') }}</td></tr>
        @endforelse
        <tr class="totals">
            <td colspan="7" class="text-start">{{ __('messages.total') }}</td>
            <td class="num">{{ number_format($commissionsTotal, 2) }}</td>
        </tr>
    </tbody>
</table>
