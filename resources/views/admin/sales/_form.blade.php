@php
    $sale = $sale ?? null;
    $action = $sale ? route('admin.sales.update', $sale) : route('admin.sales.store');
    $initialLines = $sale
        ? $sale->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'packing' => $item->packing,
            'quantity' => $item->quantity,
        ])->values()
        : [];
@endphp
<form method="post" action="{{ $action }}" id="sale-form" data-select-label="{{ __('messages.select') }}" data-remove-label="{{ __('messages.remove') }}" class="row g-3">
    @csrf
    @if($sale)
        @method('PUT')
    @endif
    @if(auth()->user()->isAdmin() && ! $sale)
        <div class="col-md-4">
            <label class="form-label">{{ __('messages.branch') }}</label>
            <select class="form-select" name="branch_id" required>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.buyer_type') }}</label>
        <select class="form-select" name="buyer_type" id="buyer_type" data-no-search="1">
            <option value="member" @selected(old('buyer_type', $sale->buyer_type ?? 'member') === 'member')>{{ __('messages.buyer_member') }}</option>
            <option value="client" @selected(old('buyer_type', $sale->buyer_type ?? '') === 'client')>{{ __('messages.buyer_client') }}</option>
        </select>
    </div>
    <div class="col-md-4" id="benefit-wrap">
        <label class="form-label">{{ __('messages.benefit_mode') }}</label>
        <select class="form-select" name="benefit_mode" id="benefit_mode" required data-no-search="1">
            <option value="pv" @selected(old('benefit_mode', $sale->benefit_mode ?? 'pv') === 'pv')>{{ __('messages.benefit_pv') }}</option>
            <option value="percent" @selected(old('benefit_mode', $sale->benefit_mode ?? '') === 'percent')>{{ __('messages.benefit_percent') }}</option>
        </select>
        <div class="form-text">{{ __('messages.sale_benefit_filter_hint') }}</div>
    </div>
    <div class="col-md-4" id="member-wrap">
        <label class="form-label">{{ __('messages.buyer_member') }}</label>
        <select class="form-select" name="member_id" data-searchable="1">
            <option value="">{{ __('messages.select') }}</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected(old('member_id', $sale->member_id ?? '') == $member->id)>{{ $member->full_name }} ({{ $member->username }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-none" id="client-wrap">
        <label class="form-label">{{ __('messages.buyer_client') }}</label>
        <select class="form-select" name="client_id" data-searchable="1">
            <option value="">{{ __('messages.select') }}</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected(old('client_id', $sale->client_id ?? '') == $client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.currency') }}</label>
        <select class="form-select" name="currency_code" data-no-search="1">
            @foreach($currencies as $currency)
                <option value="{{ $currency->code }}" @selected(old('currency_code', $sale->currency_code ?? 'USD') === $currency->code)>{{ $currency->code }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.promo') }}</label>
        <input class="form-control" type="number" step="0.01" min="0" name="promo_usd" value="{{ old('promo_usd', $sale->promo_usd ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.discount') }}</label>
        <input class="form-control" type="number" step="0.01" min="0" name="discount_usd" value="{{ old('discount_usd', $sale->discount_usd ?? '') }}" placeholder="{{ __('messages.optional') }}">
    </div>
    <div class="col-12">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('messages.products') }}</th>
                        <th>{{ __('messages.packing') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.unit_price') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="sale-lines"></tbody>
            </table>
        </div>
        <button class="btn btn-outline-success" type="button" id="add-sale-line">{{ __('messages.add_line') }}</button>
    </div>
    <div class="col-12">
        <button class="btn btn-success" type="submit">{{ $sale ? __('messages.save') : __('messages.create') }}</button>
    </div>
</form>
<select id="packing-template" class="d-none">
    <option value="tablet">{{ __('messages.tablet') }}</option>
    <option value="box">{{ __('messages.box') }}</option>
</select>
<script id="products-data" type="application/json">@json($products)</script>
<script id="sale-initial-lines" type="application/json">@json($initialLines)</script>
