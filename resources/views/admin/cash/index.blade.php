@extends('layouts.staff')
@section('title', __('messages.cash'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.cash') }}</h1>
        <div>
            <a class="btn btn-outline-success" href="{{ route('admin.reports.print', ['type' => 'cash_book']) }}">{{ __('messages.print_a4') }}</a>
            <a class="btn btn-success" href="{{ route('admin.operations.create') }}">{{ __('messages.new_operation') }}</a>
        </div>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.occurred_at') }}</th>
                            <th>{{ __('messages.direction') }}</th>
                            <th>{{ __('messages.category') }}</th>
                            <th>{{ __('messages.amount') }}</th>
                            <th>{{ __('messages.total_usd') }}</th>
                            <th>{{ __('messages.branch') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->occurred_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ __('messages.cash_'.$movement->direction) }}</td>
                                <td>
                                    {{ $movement->categoryLabel() }}
                                    @if($movement->sale)
                                        <a href="{{ route('admin.sales.show', $movement->sale) }}">{{ $movement->sale->number }}</a>
                                    @endif
                                </td>
                                <td>{{ number_format($movement->amount, 2) }} {{ $movement->currency_code }}</td>
                                <td>{{ number_format($movement->amount_usd, 2) }}</td>
                                <td>{{ $movement->branch?->name }}</td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.cash.print58', $movement) }}">58</a>
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.cash.print80', $movement) }}">80</a>
                                    @can('update', $movement)
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.cash.edit', $movement) }}">{{ __('messages.edit') }}</a>
                                        @include('partials.delete-form', ['action' => route('admin.cash.destroy', $movement)])
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $movements->links() }}</div>
        </div>
    </div>
@endsection
