@extends('layouts.staff')
@section('title', __('messages.sales'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.sales') }}</h1>
        <div>
            <a class="btn btn-outline-success" href="{{ route('admin.reports.print', ['type' => 'sales_journal']) }}">{{ __('messages.print_a4') }}</a>
            @can('create', App\Models\Sale::class)
                <a class="btn btn-success" href="{{ route('admin.sales.create') }}">{{ __('messages.new_sale') }}</a>
            @endcan
        </div>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.sale_number') }}</th>
                            <th>{{ __('messages.buyer') }}</th>
                            <th>{{ __('messages.branch') }}</th>
                            <th>{{ __('messages.total') }}</th>
                            <th>{{ __('messages.sync_status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td>{{ $sale->number }}</td>
                                <td>{{ $sale->buyerName() }}</td>
                                <td>{{ $sale->branch?->name }}</td>
                                <td>{{ number_format($sale->total_usd, 2) }} USD</td>
                                <td>{{ __('messages.'.$sale->sync_status) }}</td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.sales.show', $sale) }}">{{ __('messages.show') }}</a>
                                    @can('update', $sale)
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.sales.edit', $sale) }}">{{ __('messages.edit') }}</a>
                                    @endcan
                                    @can('delete', $sale)
                                        @include('partials.delete-form', ['action' => route('admin.sales.destroy', $sale)])
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $sales->links() }}</div>
        </div>
    </div>
@endsection
