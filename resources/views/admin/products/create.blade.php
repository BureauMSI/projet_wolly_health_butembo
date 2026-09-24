@extends('layouts.staff')
@section('title', __('messages.new_product'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.new_product') }}</h1>
        <a class="btn btn-outline-success" href="{{ route('admin.products.index') }}">{{ __('messages.back') }}</a>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.products.store') }}" class="row g-3">
                @csrf
                @include('admin.products._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    @vite('resources/js/product-form.js')
@endpush
