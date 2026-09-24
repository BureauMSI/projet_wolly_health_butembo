<div class="kpi">
    <div><strong>{{ __('messages.sales') }} :</strong> {{ number_format($salesTotal, 2) }} USD</div>
    <div><strong>{{ __('messages.pv') }} :</strong> {{ number_format($salesPv, 2) }}</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.occurred_at') }}</th>
            <th>{{ __('messages.sale_number') }}</th>
            <th>{{ __('messages.buyer') }}</th>
            <th>{{ __('messages.buyer_type') }}</th>
            <th>{{ __('messages.branch') }}</th>
            <th>{{ __('messages.cashier_role') }}</th>
            <th class="num">{{ __('messages.pv') }}</th>
            <th class="num">{{ __('messages.total_usd') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
            <tr>
                <td>{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $sale->number }}</td>
                <td class="text-start">{{ $sale->buyerName() }}</td>
                <td>{{ __('messages.buyer_'.$sale->buyer_type) }}</td>
                <td>{{ $sale->branch?->name }}</td>
                <td>{{ $sale->cashier?->name }}</td>
                <td class="num">{{ number_format($sale->items->sum('pv'), 2) }}</td>
                <td class="num">{{ number_format($sale->total_usd, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">{{ __('messages.no_records') }}</td></tr>
        @endforelse
        <tr class="totals">
            <td colspan="6" class="text-start">{{ __('messages.total') }}</td>
            <td class="num">{{ number_format($salesPv, 2) }}</td>
            <td class="num">{{ number_format($salesTotal, 2) }}</td>
        </tr>
    </tbody>
</table>
