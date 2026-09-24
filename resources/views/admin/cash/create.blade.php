@extends('layouts.staff')
@section('title', __('messages.new_movement'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.new_movement') }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.cash.store') }}" class="row g-3" id="cash-movement-form">
                @csrf
                @include('admin.cash._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/cash-form.js')
@endpush
