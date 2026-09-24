@extends('layouts.staff')
@section('title', __('messages.new_client'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.new_client') }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.clients.store') }}" class="row g-3">
                @csrf
                @include('admin.clients._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
