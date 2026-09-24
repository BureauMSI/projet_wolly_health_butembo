@php
    $institution = $institution ?? null;
    $logoSrc = $logoSrc ?? '';
    if ($logoSrc === '' && $institution?->logo_path) {
        if (file_exists(public_path($institution->logo_path))) {
            $logoSrc = asset($institution->logo_path);
        } elseif (file_exists(public_path('storage/'.$institution->logo_path))) {
            $logoSrc = asset('storage/'.$institution->logo_path);
        }
    }
    $lieu = trim(($institution?->address ?? '').(($institution?->address && ($institution?->city || $institution?->country)) ? ' — ' : '').trim(($institution?->city ?? '').($institution?->city && $institution?->country ? ', ' : '').($institution?->country ?? '')));
@endphp
<div class="ticket-head">
    @if($logoSrc)
        <img src="{{ $logoSrc }}" alt="" class="ticket-logo">
    @endif
    <div class="ticket-name">{{ $institution->name ?? __('messages.name') }}</div>
    @if($institution?->slogan)
        <div class="ticket-slogan">{{ $institution->slogan }}</div>
    @elseif($institution?->acronym)
        <div class="ticket-slogan">{{ $institution->acronym }}</div>
    @endif
    @if($lieu !== '')
        <div>{{ $lieu }}</div>
    @endif
    <div>
        @if($institution?->phone){{ __('messages.tel_short') }} : {{ $institution->phone }}@endif
        @if($institution?->phone && $institution?->email)<br>@endif
        @if($institution?->email){{ __('messages.email') }} : {{ $institution->email }}@endif
    </div>
    @if($institution?->whatsapp)
        <div>WhatsApp : {{ $institution->whatsapp }}</div>
    @endif
    <div class="ticket-legal">
        @if($institution?->tax_id){{ __('messages.fiscal_number') }} : {{ $institution->tax_id }}@endif
        @if($institution?->tax_id && $institution?->rccm) · @endif
        @if($institution?->rccm){{ __('messages.rccm') }} : {{ $institution->rccm }}@endif
        @if($institution?->id_nat)
            {{ ($institution?->tax_id || $institution?->rccm) ? ' · ' : '' }}{{ __('messages.id_nat') }} : {{ $institution->id_nat }}
        @endif
    </div>
</div>
