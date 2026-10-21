@extends('layouts.staff')
@section('title', __('messages.plan_settings'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.plan_settings') }}</h1></div>
    <div class="card surface-card mb-4">
        <div class="card-body">
            <form method="post" action="{{ route('admin.plan.settings') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.sponsorship_amount') }}</label>
                    <input class="form-control" name="sponsorship_amount_usd" value="{{ old('sponsorship_amount_usd', $settings['sponsorship_amount_usd']->value ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.membership_amount') }}</label>
                    <input class="form-control" name="membership_amount_usd" value="{{ old('membership_amount_usd', $settings['membership_amount_usd']->value ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.membership_threshold') }}</label>
                    <input class="form-control" name="membership_pv_threshold" value="{{ old('membership_pv_threshold', $settings['membership_pv_threshold']->value ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.sponsorship_pv') }}</label>
                    <input class="form-control" name="sponsorship_pv" value="{{ old('sponsorship_pv', $settings['sponsorship_pv']->value ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.username_suffix') }}</label>
                    <input class="form-control" type="number" min="1" max="8" name="username_suffix_length" value="{{ old('username_suffix_length', $settings['username_suffix_length']->value ?? 4) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.default_password') }}</label>
                    <input class="form-control" name="default_member_password" value="{{ old('default_member_password', $settings['default_member_password']->value ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.member_discount_percent') }}</label>
                    <input class="form-control" name="member_discount_percent" value="{{ old('member_discount_percent', $settings['member_discount_percent']->value ?? 0) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.equilibrium_near_count') }}</label>
                    <input class="form-control" type="number" min="1" max="32" name="equilibrium_near_count" value="{{ old('equilibrium_near_count', $settings['equilibrium_near_count']->value ?? $nearCount) }}" required>
                    <div class="form-text">{{ __('messages.equilibrium_near_count_hint') }}</div>
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <h2 class="h5">{{ __('messages.reward_tiers') }}</h2>
            <div class="table-responsive mb-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.label') }}</th>
                            <th>{{ __('messages.image') }}</th>
                            <th>{{ __('messages.min_weak_leg_pv') }}</th>
                            <th>{{ __('messages.max_pv') }}</th>
                            <th>{{ __('messages.amount_usd') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tiers as $tier)
                            <tr>
                                <td>{{ $tier->label }}</td>
                                <td>
                                    @if($tier->imageUrl())
                                        <img src="{{ $tier->imageUrl() }}" alt="" class="reward-thumb">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $tier->min_pv }}</td>
                                <td>{{ $tier->max_pv ?? '—' }}</td>
                                <td>{{ $tier->amount_usd }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.plan.tiers.destroy', $tier) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('messages.remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form method="post" action="{{ route('admin.plan.tiers.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-md-3"><input class="form-control" name="label" placeholder="{{ __('messages.label') }}" required></div>
                <div class="col-md-2"><input class="form-control" name="min_pv" placeholder="{{ __('messages.min_weak_leg_pv') }}" required></div>
                <div class="col-md-2"><input class="form-control" name="max_pv" placeholder="{{ __('messages.max_pv') }}"></div>
                <div class="col-md-2"><input class="form-control" name="amount_usd" placeholder="{{ __('messages.amount_usd') }}"></div>
                <div class="col-md-2"><input class="form-control" type="file" name="image" accept="image/*"></div>
                <div class="col-md-1"><button class="btn btn-outline-success w-100" type="submit">{{ __('messages.new_tier') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <h2 class="h5">{{ __('messages.equilibrium_rules') }}</h2>
            <p class="text-muted small">{{ __('messages.equilibrium_rules_hint', ['n' => $nearCount, 'next' => $nearCount + 1]) }}</p>
            <div class="table-responsive mb-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.scope') }}</th>
                            <th>{{ __('messages.generation') }}</th>
                            <th>{{ __('messages.amount_usd') }}</th>
                            <th>{{ __('messages.percent') }}</th>
                            <th>{{ __('messages.min_leg_pv') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $rule)
                            @php $isEditing = (int) old('editing_rule_id') === (int) $rule->id; @endphp
                            <tr>
                                <td>
                                    <select class="form-select form-select-sm" form="rule-update-{{ $rule->id }}" name="scope" data-no-search="1">
                                        <option value="generations_1_4" @selected(($isEditing ? old('scope', $rule->scope) : $rule->scope) === 'generations_1_4')>{{ __('messages.generations_1_4', ['n' => $nearCount]) }}</option>
                                        <option value="after_generation_4" @selected(($isEditing ? old('scope', $rule->scope) : $rule->scope) === 'after_generation_4')>{{ __('messages.after_generation_4', ['n' => $nearCount]) }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" form="rule-update-{{ $rule->id }}" name="generation" value="{{ $isEditing ? old('generation', $rule->generation) : $rule->generation }}" placeholder="{{ __('messages.equilibrium_number_placeholder', ['n' => $nearCount]) }}" title="{{ __('messages.equilibrium_number_hint', ['n' => $nearCount]) }}">
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" form="rule-update-{{ $rule->id }}" name="amount_usd" value="{{ $isEditing ? old('amount_usd', $rule->amount_usd) : $rule->amount_usd }}" placeholder="{{ __('messages.amount_usd') }}">
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" form="rule-update-{{ $rule->id }}" name="percent" value="{{ $isEditing ? old('percent', $rule->percent) : $rule->percent }}" placeholder="{{ __('messages.percent') }}">
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" form="rule-update-{{ $rule->id }}" name="min_leg_pv" value="{{ $isEditing ? old('min_leg_pv', $rule->min_leg_pv) : $rule->min_leg_pv }}" placeholder="{{ __('messages.min_leg_pv') }}">
                                </td>
                                <td class="text-end text-nowrap">
                                    <form id="rule-update-{{ $rule->id }}" method="post" action="{{ route('admin.plan.rules.update', $rule) }}" class="d-none">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="editing_rule_id" value="{{ $rule->id }}">
                                    </form>
                                    <button class="btn btn-sm btn-outline-success" form="rule-update-{{ $rule->id }}" type="submit">{{ __('messages.edit') }}</button>
                                    <form method="post" action="{{ route('admin.plan.rules.destroy', $rule) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('messages.remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form method="post" action="{{ route('admin.plan.rules.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <select class="form-select" name="scope" data-no-search="1">
                        <option value="generations_1_4">{{ __('messages.generations_1_4', ['n' => $nearCount]) }}</option>
                        <option value="after_generation_4">{{ __('messages.after_generation_4', ['n' => $nearCount]) }}</option>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" name="generation" placeholder="{{ __('messages.equilibrium_number_placeholder', ['n' => $nearCount]) }}" title="{{ __('messages.equilibrium_number_hint', ['n' => $nearCount]) }}"></div>
                <div class="col-md-2"><input class="form-control" name="amount_usd" placeholder="{{ __('messages.amount_usd') }}"></div>
                <div class="col-md-2"><input class="form-control" name="percent" placeholder="{{ __('messages.percent') }}"></div>
                <div class="col-md-2"><input class="form-control" name="min_leg_pv" placeholder="{{ __('messages.min_leg_pv') }}"></div>
                <div class="col-md-1"><button class="btn btn-outline-success w-100" type="submit">{{ __('messages.create') }}</button></div>
            </form>
        </div>
    </div>
@endsection
