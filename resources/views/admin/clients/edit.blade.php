@extends('layouts.staff')
@section('title', __('messages.edit'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.edit') }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.clients.update', $client) }}" class="row g-3">
                @csrf
                @method('PUT')
                @include('admin.clients._form')
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
