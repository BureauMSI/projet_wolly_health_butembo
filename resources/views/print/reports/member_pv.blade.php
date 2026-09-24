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
        <tr class="totals">
            <td colspan="3" class="text-start">{{ __('messages.total') }}</td>
            <td class="num">{{ number_format($pvEntries->sum('pv_amount'), 2) }}</td>
        </tr>
    </tbody>
</table>
