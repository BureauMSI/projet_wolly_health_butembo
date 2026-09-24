@extends('layouts.member')
@section('title', __('messages.profile'))
@section('content')
    <div class="member-page-head">
        <h1><i class="bi bi-person-circle"></i> {{ __('messages.profile') }}</h1>
        <p>{{ __('messages.profile_edit_hint') }}</p>
        <a class="btn btn-outline-success btn-sm" href="{{ route('member.reports.print', 'member_sheet') }}">{{ __('messages.membership_sheet') }}</a>
    </div>
    <form method="post" action="{{ route('member.profile.update') }}" enctype="multipart/form-data" id="member-profile-form">
        @csrf
        @method('PUT')
        <section class="member-sheet member-profile-card">
            <div class="member-photo-picker">
                <div class="member-photo-preview">
                    <img id="photo-preview" src="{{ $member->photo_path ? Storage::url($member->photo_path) : '' }}" alt="" class="{{ $member->photo_path ? '' : 'd-none' }}">
                    <span id="photo-initials" class="{{ $member->photo_path ? 'd-none' : '' }}">{{ mb_strtoupper(mb_substr($member->full_name, 0, 1)) }}</span>
                </div>
                <div>
                    <label class="btn btn-outline-success" for="photo"><i class="bi bi-camera"></i> {{ __('messages.change_photo') }}</label>
                    <input class="d-none" id="photo" type="file" name="photo" accept="image/*">
                    <p class="member-hint mb-0 mt-2">{{ __('messages.photo_optional_hint') }}</p>
                    @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>
        <section class="member-sheet">
            <h2><i class="bi bi-person-vcard"></i> {{ __('messages.edit_profile') }}</h2>
            <div class="mb-3">
                <label class="form-label" for="username">{{ __('messages.username') }}</label>
                <div class="input-group member-input-icon">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="username" class="form-control" value="{{ $member->username }}" disabled readonly>
                </div>
                <div class="form-text">{{ __('messages.username_locked') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="full_name">{{ __('messages.full_name') }}</label>
                <div class="input-group member-input-icon">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input id="full_name" class="form-control @error('full_name') is-invalid @enderror" name="full_name" value="{{ old('full_name', $member->full_name) }}" required maxlength="255" autocomplete="name">
                </div>
                @error('full_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="phone">{{ __('messages.phone') }}</label>
                <div class="input-group member-input-icon">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input id="phone" class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" value="{{ old('phone', $member->phone) }}" maxlength="50" autocomplete="tel">
                </div>
                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label" for="gender">{{ __('messages.gender') }}</label>
                    <select id="gender" class="form-select @error('gender') is-invalid @enderror" name="gender">
                        <option value="">{{ __('messages.select') }}</option>
                        <option value="male" @selected(old('gender', $member->gender)==='male')>{{ __('messages.male') }}</option>
                        <option value="female" @selected(old('gender', $member->gender)==='female')>{{ __('messages.female') }}</option>
                        <option value="other" @selected(old('gender', $member->gender)==='other')>{{ __('messages.other') }}</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label" for="birth_date">{{ __('messages.birth_date') }}</label>
                    <input id="birth_date" class="form-control @error('birth_date') is-invalid @enderror" type="date" name="birth_date" value="{{ old('birth_date', $member->birth_date?->format('Y-m-d')) }}">
                    @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-3 mb-3">
                <label class="form-label" for="address">{{ __('messages.address') }}</label>
                <div class="input-group member-input-icon">
                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                    <input id="address" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', $member->address) }}" maxlength="255" autocomplete="street-address">
                </div>
                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="id_document">{{ __('messages.id_document') }} ({{ __('messages.optional') }})</label>
                @if($member->id_document_path)
                    <p class="small text-success mb-1"><i class="bi bi-file-earmark-check"></i> {{ __('messages.document_on_file') }}</p>
                @endif
                <input id="id_document" class="form-control @error('id_document') is-invalid @enderror" type="file" name="id_document" accept="image/*,.pdf">
                @error('id_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-success w-100" type="submit"><i class="bi bi-check-lg"></i> {{ __('messages.save') }}</button>
        </section>
    </form>
    <section class="member-sheet">
        <div class="member-row">
            <div class="member-row-main"><i class="bi bi-hash"></i><span>{{ __('messages.member_code') }}</span></div>
            <strong>{{ $member->member_code }}</strong>
        </div>
        <div class="member-row">
            <div class="member-row-main"><i class="bi bi-people"></i><span>{{ __('messages.sponsor') }}</span></div>
            <strong>{{ $member->sponsor?->full_name ?? '—' }}</strong>
        </div>
        <div class="member-row mb-0">
            <div class="member-row-main"><i class="bi bi-diagram-3"></i><span>{{ __('messages.placement') }}</span></div>
            <strong>
                @if($member->placementParent)
                    {{ $member->placementParent->full_name }} ({{ __('messages.'.$member->placement_side) }})
                @elseif($member->isTreeRoot())
                    {{ __('messages.root_member') }}
                @else
                    {{ __('messages.awaiting_placement') }}
                @endif
            </strong>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/member-profile.js')
@endpush
