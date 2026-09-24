@extends('layouts.staff')
@section('title', __('messages.new_branch'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.new_branch') }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.branches.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.name_field') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.code') }}</label>
                    <input class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.address') }}</label>
                    <input class="form-control" name="address" value="{{ old('address') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
