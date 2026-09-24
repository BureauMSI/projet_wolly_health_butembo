@extends('layouts.staff')
@section('title', __('messages.edit'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.edit') }} · {{ $member->member_code }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.members.update', $member) }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.full_name') }}</label>
                    <input class="form-control" name="full_name" value="{{ old('full_name', $member->full_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone', $member->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.gender') }}</label>
                    <select class="form-select" name="gender" data-no-search="1">
                        <option value="">{{ __('messages.select') }}</option>
                        <option value="male" @selected(old('gender', $member->gender)==='male')>{{ __('messages.male') }}</option>
                        <option value="female" @selected(old('gender', $member->gender)==='female')>{{ __('messages.female') }}</option>
                        <option value="other" @selected(old('gender', $member->gender)==='other')>{{ __('messages.other') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.birth_date') }}</label>
                    <input class="form-control" type="date" name="birth_date" value="{{ old('birth_date', optional($member->birth_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.status') }}</label>
                    <select class="form-select" name="status" required data-no-search="1">
                        <option value="active" @selected(old('status', $member->status)==='active')>{{ __('messages.active') }}</option>
                        <option value="inactive" @selected(old('status', $member->status)==='inactive')>{{ __('messages.inactive') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('messages.address') }}</label>
                    <input class="form-control" name="address" value="{{ old('address', $member->address) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.photo') }}</label>
                    <input class="form-control" type="file" name="photo" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.id_document') }}</label>
                    <input class="form-control" type="file" name="id_document">
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
