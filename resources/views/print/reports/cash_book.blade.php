<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.occurred_at') }}</th>
            <th>{{ __('messages.category') }}</th>
            <th>{{ __('messages.description') }}</th>
            <th class="num">{{ __('messages.cash_in') }}</th>
            <th class="num">{{ __('messages.cash_out') }}</th>
            <th class="num">{{ __('messages.running_balance') }}</th>
        </tr>
    </thead>
    <tbody>
        <tr class="ligne-initiale">
            <td>{{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }}</td>
            <td colspan="2" class="text-start">{{ __('messages.opening_balance') }}</td>
            <td class="num"></td>
            <td class="num"></td>
            <td class="num">{{ number_format($opening, 2) }}</td>
        </tr>
        @foreach($rows as $row)
            @php $movement = $row['movement']; @endphp
            <tr class="{{ $row['out'] ? 'ligne-sortie' : '' }}">
                <td>{{ $movement->occurred_at?->format('d/m/Y') }}</td>
                <td>{{ $movement->categoryLabel() }}</td>
                <td class="text-start">{{ $movement->description }}</td>
                <td class="num">{{ $row['in'] ? number_format($row['in'], 2) : '—' }}</td>
                <td class="num">{{ $row['out'] ? number_format($row['out'], 2) : '—' }}</td>
                <td class="num">{{ number_format($row['balance'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="totals">
            <td colspan="3" class="text-start">{{ __('messages.closing_balance') }}</td>
            <td class="num">{{ number_format($cashIn, 2) }}</td>
            <td class="num">{{ number_format($cashOut, 2) }}</td>
            <td class="num">{{ number_format($closing, 2) }}</td>
        </tr>
    </tbody>
</table>
