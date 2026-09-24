@extends('layouts.staff')
@section('title', __('messages.products'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.products') }}</h1>
        @can('create', App\Models\Product::class)
            <a class="btn btn-success" href="{{ route('admin.products.create') }}">{{ __('messages.new_product') }}</a>
        @endcan
    </div>
    <p class="text-muted">{{ __('messages.no_stock_note') }}</p>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.code') }}</th>
                            <th>{{ __('messages.name_field') }}</th>
                            <th>{{ __('messages.product_benefit_type') }}</th>
                            <th>{{ __('messages.unit_price_usd') }}</th>
                            <th>{{ __('messages.member_unit_price_usd') }}</th>
                            <th>{{ __('messages.pv_or_percent') }}</th>
                            <th>{{ __('messages.box_price_usd') }}</th>
                            <th>{{ __('messages.member_box_price_usd') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>
                                    @if($product->isPercentType())
                                        {{ __('messages.product_type_percent') }}
                                    @else
                                        {{ __('messages.product_type_pv') }}
                                    @endif
                                </td>
                                <td>{{ number_format($product->unit_price_usd, 2) }}</td>
                                <td>{{ number_format($product->member_unit_price_usd ?? $product->unit_price_usd, 2) }}</td>
                                <td>
                                    @if($product->isPercentType())
                                        {{ number_format($product->commission_percent, 2) }} %
                                    @else
                                        {{ number_format($product->pv_per_tablet, 2) }} PV
                                    @endif
                                </td>
                                <td>{{ number_format($product->box_price_usd, 2) }}</td>
                                <td>{{ number_format($product->member_box_price_usd ?? $product->box_price_usd, 2) }}</td>
                                <td class="text-end text-nowrap">
                                    @can('update', $product)
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.products.edit', $product) }}">{{ __('messages.edit') }}</a>
                                    @endcan
                                    @can('delete', $product)
                                        @include('partials.delete-form', ['action' => route('admin.products.destroy', $product)])
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $products->links() }}</div>
        </div>
    </div>
@endsection
