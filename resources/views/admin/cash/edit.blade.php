@extends('layouts.staff')
@section('title', __('messages.edit'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.edit') }}</h1>
        <div>
            <a class="btn btn-outline-success" href="{{ route('admin.cash.print58', $movement) }}">{{ __('messages.print_voucher_58') }}</a>
            <a class="btn btn-outline-success" href="{{ route('admin.cash.print80', $movement) }}">{{ __('messages.print_voucher_80') }}</a>
        </div>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.cash.update', $movement) }}" class="row g-3" id="cash-movement-form">
                @csrf
                @method('PUT')
                @include('admin.cash._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/cash-form.js')
@endpush
