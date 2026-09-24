<div class="kpi">
    <div><strong>{{ __('messages.members') }} :</strong> {{ $membersCount }}</div>
    <div><strong>{{ __('messages.pv') }} :</strong> {{ number_format($pvTotal, 2) }}</div>
    <div><strong>{{ __('messages.gains_unpaid') }} :</strong> {{ number_format($gainsUnpaidTotal, 2) }} USD</div>
    <div><strong>{{ __('messages.gains_paid') }} :</strong> {{ number_format($gainsPaidTotal, 2) }} USD</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.member_code') }}</th>
            <th>{{ __('messages.full_name') }}</th>
            <th>{{ __('messages.phone') }}</th>
            <th>{{ __('messages.sponsor') }}</th>
            <th class="num">{{ __('messages.pv_pending') }}</th>
            <th class="num">{{ __('messages.pv_confirmed') }}</th>
            <th class="num">{{ __('messages.weak_leg_pv') }}</th>
            <th class="num">{{ __('messages.gains_unpaid') }}</th>
            <th class="num">{{ __('messages.gains_paid') }}</th>
            <th class="num">{{ __('messages.report_period_gains') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['member']->member_code }}</td>
                <td class="text-start">{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->phone }}</td>
                <td class="text-start">{{ $row['member']->sponsor?->full_name }}</td>
                <td class="num">{{ number_format($row['pvPending'], 2) }}</td>
                <td class="num">{{ number_format($row['pvConfirmed'], 2) }}</td>
                <td class="num">{{ number_format($row['weakLegPv'], 2) }}</td>
                <td class="num">{{ number_format($row['gainsUnpaid'], 2) }}</td>
                <td class="num">{{ number_format($row['gainsPaid'], 2) }}</td>
                <td class="num">{{ number_format($row['gainsPeriodPaid'] + $row['gainsPeriodUnpaid'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="10">{{ __('messages.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table>
