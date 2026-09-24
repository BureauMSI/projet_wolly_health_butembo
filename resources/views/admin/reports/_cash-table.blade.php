<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>{{ __('messages.occurred_at') }}</th>
                <th>{{ __('messages.category') }}</th>
                <th>{{ __('messages.description') }}</th>
                <th>{{ __('messages.total_usd') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement->occurred_at?->format('Y-m-d') }}</td>
                    <td>{{ $movement->categoryLabel() }}</td>
                    <td>{{ $movement->description }}</td>
                    <td>{{ number_format($movement->amount_usd, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">{{ __('messages.no_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
