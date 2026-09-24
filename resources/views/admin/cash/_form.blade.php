@php $movement = $movement ?? null; @endphp
@if(auth()->user()->isAdmin())
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.branch') }}</label>
        <select class="form-select" name="branch_id" required data-searchable="1">
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $movement->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
@endif
<div class="col-md-4">
    <label class="form-label">{{ __('messages.direction') }}</label>
    <select class="form-select" name="direction" id="cash-direction" data-no-search="1">
        <option value="in" @selected(old('direction', $movement->direction ?? '') === 'in')>{{ __('messages.cash_in') }}</option>
        <option value="out" @selected(old('direction', $movement->direction ?? '') === 'out')>{{ __('messages.cash_out') }}</option>
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">{{ __('messages.category') }}</label>
    <select class="form-select" name="category" id="cash-category" data-searchable="1">
        @foreach($operationTypes as $type)
            <option
                value="{{ $type->code }}"
                data-allows="{{ $type->direction }}"
                @selected(old('category', $movement->category ?? '') === $type->code)
            >{{ $type->displayName() }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">{{ __('messages.amount') }}</label>
    <input class="form-control" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $movement->amount ?? '') }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">{{ __('messages.currency') }}</label>
    <select class="form-select" name="currency_code" data-no-search="1">
        @foreach($currencies as $currency)
            <option value="{{ $currency->code }}" @selected(old('currency_code', $movement->currency_code ?? '') === $currency->code)>{{ $currency->code }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">{{ __('messages.occurred_at') }}</label>
    <input class="form-control" type="datetime-local" name="occurred_at" value="{{ old('occurred_at', isset($movement) && $movement->occurred_at ? $movement->occurred_at->format('Y-m-d\\TH:i') : '') }}">
</div>
<div class="col-12">
    <label class="form-label">{{ __('messages.description') }}</label>
    <input class="form-control" name="description" value="{{ old('description', $movement->description ?? '') }}">
</div>
