@php $product = $product ?? null; @endphp
@if($product)
<div class="col-md-4">
    <label class="form-label">{{ __('messages.code') }}</label>
    <input class="form-control" value="{{ $product->code }}" readonly>
    <div class="form-text">{{ __('messages.product_code_readonly') }}</div>
</div>
<div class="col-md-5">
@else
<div class="col-md-8">
@endif
    <label class="form-label">{{ __('messages.name_field') }}</label>
    <input class="form-control" name="name" value="{{ old('name', $product->name ?? '') }}" required>
    @unless($product)
        <div class="form-text">{{ __('messages.product_code_auto') }}</div>
    @endunless
</div>
<div class="col-md-3 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_active" id="product-active" value="1" @checked(old('is_active', $product->is_active ?? true))>
        <label class="form-check-label" for="product-active">{{ __('messages.is_active') }}</label>
    </div>
</div>

<div class="col-md-4">
    <label class="form-label" for="benefit_type">{{ __('messages.product_benefit_type') }}</label>
    <select class="form-select" name="benefit_type" id="benefit_type" required data-no-search="1">
        <option value="pv" @selected(old('benefit_type', $product->benefit_type ?? 'pv') === 'pv')>{{ __('messages.product_type_pv') }}</option>
        <option value="percent" @selected(old('benefit_type', $product->benefit_type ?? '') === 'percent')>{{ __('messages.product_type_percent') }}</option>
    </select>
    <div class="form-text">{{ __('messages.product_benefit_type_hint') }}</div>
</div>

<div class="col-12">
    <h2 class="h6 text-uppercase text-muted mb-0">{{ __('messages.client_price') }}</h2>
</div>
<div class="col-6 col-md-3">
    <label class="form-label">{{ __('messages.unit_price_usd') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" name="unit_price_usd" value="{{ old('unit_price_usd', $product->unit_price_usd ?? '') }}" required>
</div>
<div class="col-6 col-md-3">
    <label class="form-label">{{ __('messages.box_price_usd') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" name="box_price_usd" value="{{ old('box_price_usd', $product->box_price_usd ?? '') }}" required>
</div>
<div class="col-6 col-md-3 product-pv-fields">
    <label class="form-label">{{ __('messages.pv_per_tablet') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" name="pv_per_tablet" id="pv_per_tablet" value="{{ old('pv_per_tablet', $product->pv_per_tablet ?? '') }}">
</div>
<div class="col-6 col-md-3 product-pv-fields">
    <label class="form-label">{{ __('messages.box_pv') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" name="box_pv" id="box_pv" value="{{ old('box_pv', $product->box_pv ?? '') }}">
</div>
<div class="col-6 col-md-3 product-percent-fields d-none">
    <label class="form-label">{{ __('messages.commission_percent') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" max="100" name="commission_percent" id="commission_percent" value="{{ old('commission_percent', $product->commission_percent ?? 0) }}">
</div>

<div class="col-12">
    <h2 class="h6 text-uppercase text-muted mb-0">{{ __('messages.member_price') }}</h2>
</div>
<div class="col-6 col-md-3">
    <label class="form-label">{{ __('messages.member_unit_price_usd') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" name="member_unit_price_usd" value="{{ old('member_unit_price_usd', $product->member_unit_price_usd ?? $product->unit_price_usd ?? '') }}" required>
</div>
<div class="col-6 col-md-3">
    <label class="form-label">{{ __('messages.member_box_price_usd') }}</label>
    <input class="form-control" type="number" step="0.01" min="0" name="member_box_price_usd" value="{{ old('member_box_price_usd', $product->member_box_price_usd ?? $product->box_price_usd ?? '') }}" required>
</div>
