@extends('layouts.staff')
@section('title', __('messages.cash_operations'))
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ __('messages.cash_operations') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.cash_operations_intro') }}</p>
        </div>
        <div>
            <a class="btn btn-outline-success" href="{{ route('admin.reports.print', ['type' => 'cash_book']) }}">{{ __('messages.print_a4') }}</a>
            <a class="btn btn-success" href="{{ route('admin.operations.create') }}">{{ __('messages.new_operation') }}</a>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <select class="form-select" name="direction" onchange="this.form.submit()" data-no-search="1">
                <option value="" @selected($direction === null || $direction === '')>{{ __('messages.filter_all') }}</option>
                <option value="in" @selected($direction === 'in')>{{ __('messages.cash_in') }}</option>
                <option value="out" @selected($direction === 'out')>{{ __('messages.cash_out') }}</option>
            </select>
        </div>
    </form>

    @if(auth()->user()->isAdmin())
        <div class="card surface-card mb-3">
            <div class="card-body">
                <h2 class="h6">{{ __('messages.new_operation_type') }}</h2>
                <form method="post" action="{{ route('admin.operations.types.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label">{{ __('messages.label') }}</label>
                        <input class="form-control" name="label" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.operation_type_direction') }}</label>
                        <select class="form-select" name="direction" required data-no-search="1">
                            <option value="in">{{ __('messages.cash_in') }}</option>
                            <option value="out">{{ __('messages.cash_out') }}</option>
                            <option value="both">{{ __('messages.direction_both') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-success" type="submit">{{ __('messages.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.occurred_at') }}</th>
                            <th>{{ __('messages.direction') }}</th>
                            <th>{{ __('messages.category') }}</th>
                            <th>{{ __('messages.description') }}</th>
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
                                <td>{{ $movement->categoryLabel() }}</td>
                                <td>{{ $movement->description }}</td>
                                <td>{{ number_format($movement->amount, 2) }} {{ $movement->currency_code }}</td>
                                <td>{{ number_format($movement->amount_usd, 2) }}</td>
                                <td>{{ $movement->branch?->name }}</td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.cash.print58', $movement) }}">58</a>
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.cash.print80', $movement) }}">80</a>
                                    @can('update', $movement)
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.operations.edit', $movement) }}">{{ __('messages.edit') }}</a>
                                        @include('partials.delete-form', ['action' => route('admin.operations.destroy', $movement)])
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $movements->links() }}</div>
        </div>
    </div>
@endsection
