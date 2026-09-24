@extends('layouts.staff')
@section('title', __('messages.assignment'))
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ __('messages.assignment') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.assignment_intro') }}</p>
        </div>
        <a class="btn btn-outline-success" href="{{ route('admin.members.tree') }}">{{ __('messages.tree') }}</a>
    </div>
    @if($unplaced->isEmpty())
        <div class="card surface-card">
            <div class="card-body">
                <p class="text-muted mb-0">{{ __('messages.no_members_awaiting_placement') }}</p>
            </div>
        </div>
    @else
        @foreach($unplaced as $member)
            <div class="card surface-card mb-3 {{ $focusId === $member->id ? 'border-warning' : '' }}" id="member-{{ $member->id }}">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3">
                            <div class="fw-semibold">{{ $member->full_name }}</div>
                            <div class="small text-muted">{{ $member->username }}</div>
                            <a class="small" href="{{ route('admin.members.show', $member) }}">{{ __('messages.show') }}</a>
                        </div>
                        <div class="col-lg-9">
                            @can('update', $member)
                                <form method="post" action="{{ route('admin.placements.store', $member) }}" class="row g-2">
                                    @csrf
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('messages.sponsor') }}</label>
                                        <select class="form-select @error('sponsor_id') {{ $focusId === $member->id ? 'is-invalid' : '' }} @enderror" name="sponsor_id" required data-searchable="1">
                                            <option value="">{{ __('messages.select') }}</option>
                                            @foreach($sponsors as $sponsor)
                                                @if($sponsor->id !== $member->id)
                                                    <option value="{{ $sponsor->id }}" @selected($focusId === $member->id && old('sponsor_id') == $sponsor->id)>{{ $sponsor->full_name }} ({{ $sponsor->username }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <div class="form-text">{{ __('messages.sponsor_not_placement') }}</div>
                                        @if($focusId === $member->id)
                                            @error('sponsor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('messages.placement_parent') }}</label>
                                        <select class="form-select @error('placement_parent_id') {{ $focusId === $member->id ? 'is-invalid' : '' }} @enderror" name="placement_parent_id" required data-searchable="1">
                                            <option value="">{{ __('messages.select') }}</option>
                                            @foreach($treeMembers as $parent)
                                                @if($parent->id !== $member->id)
                                                    <option value="{{ $parent->id }}" @selected($focusId === $member->id && old('placement_parent_id') == $parent->id)>{{ $parent->full_name }} ({{ $parent->username }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @if($focusId === $member->id)
                                            @error('placement_parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('messages.placement_side') }}</label>
                                        <select class="form-select @error('placement_side') {{ $focusId === $member->id ? 'is-invalid' : '' }} @enderror" name="placement_side" required data-no-search="1">
                                            <option value="">{{ __('messages.select') }}</option>
                                            <option value="left" @selected($focusId === $member->id && old('placement_side') === 'left')>{{ __('messages.left') }}</option>
                                            <option value="right" @selected($focusId === $member->id && old('placement_side') === 'right')>{{ __('messages.right') }}</option>
                                        </select>
                                        @if($focusId === $member->id)
                                            @error('placement_side')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-success w-100" type="submit">{{ __('messages.assign_placement') }}</button>
                                    </div>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="mt-3">{{ $unplaced->links() }}</div>
    @endif
@endsection
