@extends('layouts.staff')
@section('title', __('messages.edit_sale'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.edit_sale') }} · {{ $sale->number }}</h1></div>
    <p class="text-muted">{{ __('messages.sale_credits_cash') }}</p>
    <div class="card surface-card">
        <div class="card-body">
            @include('admin.sales._form')
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/sale-form.js')
@endpush
