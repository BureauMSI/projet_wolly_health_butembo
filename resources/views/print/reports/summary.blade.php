<div class="kpi">
    <div><strong>{{ __('messages.sales') }} :</strong> {{ number_format($salesTotal, 2) }} USD</div>
    <div><strong>{{ __('messages.cash_in') }} :</strong> {{ number_format($cashIn, 2) }} USD</div>
    <div><strong>{{ __('messages.cash_out') }} :</strong> {{ number_format($cashOut, 2) }} USD</div>
    <div><strong>{{ __('messages.net_cash') }} :</strong> {{ number_format($cashNet, 2) }} USD</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.category') }}</th>
            <th class="num">{{ __('messages.total_usd') }}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-start">{{ __('messages.sales') }}</td>
            <td class="num">{{ number_format($salesTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="text-start">{{ __('messages.cash_in') }}</td>
            <td class="num">{{ number_format($cashIn, 2) }}</td>
        </tr>
        <tr class="ligne-sortie">
            <td class="text-start">{{ __('messages.cash_out') }}</td>
            <td class="num">{{ number_format($cashOut, 2) }}</td>
        </tr>
        <tr>
            <td class="text-start">{{ __('messages.net_cash') }}</td>
            <td class="num">{{ number_format($cashNet, 2) }}</td>
        </tr>
        <tr>
            <td class="text-start">{{ __('messages.report_commissions_paid') }}</td>
            <td class="num">{{ number_format($commissionsPaidTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="text-start">{{ __('messages.report_commissions_unpaid') }}</td>
            <td class="num">{{ number_format($commissionsUnpaidTotal, 2) }}</td>
        </tr>
    </tbody>
</table>
