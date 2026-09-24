@extends('layouts.staff')
@section('title', __('messages.institution'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.institution') }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.institution.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.name_field') }}</label>
                    <input class="form-control" name="name" value="{{ old('name', $institution->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.acronym') }}</label>
                    <input class="form-control" name="acronym" value="{{ old('acronym', $institution->acronym) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.locale') }}</label>
                    <select class="form-select" name="default_locale" data-no-search="1">
                        <option value="fr" @selected(old('default_locale', $institution->default_locale)==='fr')>{{ __('messages.french') }}</option>
                        <option value="sw" @selected(old('default_locale', $institution->default_locale)==='sw')>{{ __('messages.swahili') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.slogan') }}</label>
                    <input class="form-control" name="slogan" value="{{ old('slogan', $institution->slogan) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.email') }}</label>
                    <input class="form-control" name="email" value="{{ old('email', $institution->email) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone', $institution->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.whatsapp_phone') }}</label>
                    <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $institution->whatsapp) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('messages.address') }}</label>
                    <input class="form-control" name="address" value="{{ old('address', $institution->address) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.city') }}</label>
                    <input class="form-control" name="city" value="{{ old('city', $institution->city) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.country') }}</label>
                    <input class="form-control" name="country" value="{{ old('country', $institution->country ?? 'RDC') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.default_currency') }}</label>
                    <input class="form-control" name="default_currency_code" value="{{ old('default_currency_code', $institution->default_currency_code ?? 'USD') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.rccm') }}</label>
                    <input class="form-control" name="rccm" value="{{ old('rccm', $institution->rccm) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.tax_id') }}</label>
                    <input class="form-control" name="tax_id" value="{{ old('tax_id', $institution->tax_id) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.id_nat') }}</label>
                    <input class="form-control" name="id_nat" value="{{ old('id_nat', $institution->id_nat) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('messages.invoice_footer') }}</label>
                    <textarea class="form-control" name="invoice_footer" rows="2">{{ old('invoice_footer', $institution->invoice_footer) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
