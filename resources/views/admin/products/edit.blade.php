@extends('layouts.staff')
@section('title', __('messages.edit_product'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.edit_product') }}</h1>
        <a class="btn btn-outline-success" href="{{ route('admin.products.index') }}">{{ __('messages.back') }}</a>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.products.update', $product) }}" class="row g-3">
                @csrf
                @method('PUT')
                @include('admin.products._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    @vite('resources/js/product-form.js')
@endpush
