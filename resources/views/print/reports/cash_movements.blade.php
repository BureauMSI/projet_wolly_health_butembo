<div class="kpi">
    <div><strong>{{ __('messages.cash_in') }} :</strong> {{ number_format($cashIn, 2) }} USD</div>
    <div><strong>{{ __('messages.cash_out') }} :</strong> {{ number_format($cashOut, 2) }} USD</div>
    <div><strong>{{ __('messages.net_cash') }} :</strong> {{ number_format($cashNet, 2) }} USD</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.occurred_at') }}</th>
            <th>{{ __('messages.direction') }}</th>
            <th>{{ __('messages.category') }}</th>
            <th>{{ __('messages.description') }}</th>
            <th>{{ __('messages.branch') }}</th>
            <th class="num">{{ __('messages.amount') }}</th>
            <th class="num">{{ __('messages.total_usd') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cash as $movement)
            <tr class="{{ $movement->direction === 'out' ? 'ligne-sortie' : '' }}">
                <td>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td>
                <td>{{ __('messages.cash_'.$movement->direction) }}</td>
                <td>{{ $movement->categoryLabel() }}</td>
                <td class="text-start">{{ $movement->description }}</td>
                <td>{{ $movement->branch?->name }}</td>
                <td class="num">{{ number_format($movement->amount, 2) }} {{ $movement->currency_code }}</td>
                <td class="num">{{ number_format($movement->amount_usd, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">{{ __('messages.no_records') }}</td></tr>
        @endforelse
        <tr class="totals">
            <td colspan="6" class="text-start">{{ __('messages.net_cash') }}</td>
            <td class="num">{{ number_format($cashNet, 2) }}</td>
        </tr>
    </tbody>
</table>
