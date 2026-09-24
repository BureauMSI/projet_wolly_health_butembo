@extends('layouts.staff')
@section('title', __('messages.new_member'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.new_member') }}</h1></div>
    <p class="text-muted">{{ __('messages.photo_optional_hint') }} {{ __('messages.placement_after_registration') }} {{ __('messages.sponsor_at_assignment') }}</p>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" class="row g-3" autocomplete="off" id="member-form">
                @csrf
                <div class="col-12">
                    <label class="form-label">{{ __('messages.membership_type') }}</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="membership_type" id="membership-direct" value="direct" @checked($selectedType !== 'indirect')>
                            <label class="form-check-label" for="membership-direct">{{ __('messages.membership_direct') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="membership_type" id="membership-indirect" value="indirect" @checked($selectedType === 'indirect')>
                            <label class="form-check-label" for="membership-indirect">{{ __('messages.membership_indirect') }}</label>
                        </div>
                    </div>
                    <div class="form-text">{{ __('messages.indirect_membership_hint') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.full_name') }}</label>
                    <input class="form-control" name="full_name" value="{{ old('full_name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.gender') }}</label>
                    <select class="form-select" name="gender" data-no-search="1">
                        <option value="">{{ __('messages.select') }}</option>
                        <option value="male" @selected(old('gender')==='male')>{{ __('messages.male') }}</option>
                        <option value="female" @selected(old('gender')==='female')>{{ __('messages.female') }}</option>
                        <option value="other" @selected(old('gender')==='other')>{{ __('messages.other') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.birth_date') }}</label>
                    <input class="form-control" type="date" name="birth_date" value="{{ old('birth_date') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.address') }}</label>
                    <input class="form-control" name="address" value="{{ old('address') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.username') }}</label>
                    <input class="form-control" name="username" value="{{ old('username') }}" required autocomplete="off">
                    <div class="form-text">{{ __('messages.username_hint') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.password') }}</label>
                    <input class="form-control" type="password" name="password" autocomplete="new-password">
                    <div class="form-text">{{ __('messages.password_if_empty') }}</div>
                </div>
                @if(auth()->user()->isAdmin())
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.registration_branch') }}</label>
                        <select class="form-select" name="registration_branch_id" required>
                            <option value="">{{ __('messages.select') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('registration_branch_id')==$branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.registration_code') }}</label>
                        <div class="form-control bg-light">
                            @if(($availableRegistrationCodes ?? 0) > 0)
                                {{ __('messages.registration_code_auto_hint', ['count' => $availableRegistrationCodes, 'branch' => $branchName ?? '']) }}
                            @else
                                <span class="text-danger">{{ __('messages.registration_code_exhausted') }}</span>
                            @endif
                        </div>
                        <div class="form-text">{{ __('messages.registration_code_auto_help') }}</div>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.locale') }}</label>
                    <select class="form-select" name="locale" data-no-search="1">
                        <option value="fr" @selected(old('locale', 'fr')==='fr')>{{ __('messages.french') }}</option>
                        <option value="sw" @selected(old('locale')==='sw')>{{ __('messages.swahili') }}</option>
                    </select>
                </div>
                <div class="col-md-4 @if($selectedType === 'indirect') d-none @endif" id="direct-membership-wrap">
                    <label class="form-label">{{ __('messages.membership_amount') }}</label>
                    <input class="form-control" type="text" value="{{ number_format((float) $membershipAmount, 2, '.', '') }} USD" readonly>
                    <div class="form-text">{{ __('messages.membership_pv_hint', ['pv' => $membershipPv]) }}</div>
                </div>
                <div class="col-md-8 @if($selectedType !== 'indirect') d-none @endif" id="indirect-membership-wrap">
                    <label class="form-label">{{ __('messages.source_client') }}</label>
                    <select class="form-select" name="source_client_id" id="source_client_id" data-searchable="1">
                        <option value="">{{ __('messages.select') }}</option>
                        @foreach($eligibleClients as $client)
                            <option value="{{ $client->id }}" @selected((int) $selectedClientId === $client->id)>
                                {{ $client->name }} — {{ __('messages.referrer') }}: {{ $client->referrer?->full_name }} ({{ number_format((float) $client->accumulated_pv, 2) }} PV)
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('messages.indirect_client_hint', ['pv' => $membershipPv]) }}</div>
                    @if($eligibleClients->isEmpty())
                        <div class="text-warning small mt-1">{{ __('messages.no_eligible_clients') }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.photo') }} ({{ __('messages.optional') }})</label>
                    <input class="form-control" type="file" name="photo" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.id_document') }} ({{ __('messages.optional') }})</label>
                    <input class="form-control" type="file" name="id_document">
                </div>
                <div class="col-12">
                    @php
                        $canSubmit = auth()->user()->isAdmin() || (($availableRegistrationCodes ?? 0) > 0);
                    @endphp
                    <button class="btn btn-success" type="submit" @disabled(! $canSubmit)>{{ __('messages.create') }}</button>
                    @unless($canSubmit)
                        <div class="text-danger small mt-2">{{ __('messages.registration_code_exhausted') }}</div>
                    @endunless
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/member-form.js')
@endpush
