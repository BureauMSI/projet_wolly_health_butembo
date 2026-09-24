@php
    $institution = $institution ?? null;
    $logoSrc = '';
    if ($institution?->logo_path) {
        if (file_exists(public_path($institution->logo_path))) {
            $logoSrc = asset($institution->logo_path);
        } elseif (file_exists(public_path('storage/'.$institution->logo_path))) {
            $logoSrc = asset('storage/'.$institution->logo_path);
        }
    }
    $lieu = trim(($institution?->address ?? '').(($institution?->address && ($institution?->city || $institution?->country)) ? ' — ' : '').trim(($institution?->city ?? '').($institution?->city && $institution?->country ? ', ' : '').($institution?->country ?? '')));
    $periodLabel = $periodLabel ?? __('messages.report_period_from_to', [
        'from' => \Illuminate\Support\Carbon::parse($from)->format('d/m/Y'),
        'to' => \Illuminate\Support\Carbon::parse($to)->format('d/m/Y'),
    ]);
    $sousTitre = ($branch ?? null)?->name ? $periodLabel.' · '.$branch->name : $periodLabel;
    if (! empty($subjectMember)) {
        $sousTitre = $periodLabel.' · '.$subjectMember->member_code.' — '.$subjectMember->full_name;
    }
@endphp
<table class="entete-institution" cellpadding="0" cellspacing="0">
    <tr>
        <td class="ei-left">
            <div class="ei-nom">{{ $institution->name ?? __('messages.name') }}</div>
            @if($institution?->slogan)
                <div class="ei-slogan">{{ $institution->slogan }}</div>
            @elseif($institution?->acronym)
                <div class="ei-slogan">{{ $institution->acronym }}</div>
            @endif
            @if($lieu !== '')
                <div class="ei-ligne">{{ $lieu }}</div>
            @endif
            <div class="ei-ligne">
                @if($institution?->phone){{ __('messages.tel_short') }} : {{ $institution->phone }}@endif
                @if($institution?->phone && $institution?->email) | @endif
                @if($institution?->email){{ __('messages.email') }} : {{ $institution->email }}@endif
            </div>
            <div class="ei-legales">
                @if($institution?->tax_id){{ __('messages.fiscal_number') }} : {{ $institution->tax_id }}@endif
                @if($institution?->tax_id && $institution?->rccm) | @endif
                @if($institution?->rccm){{ __('messages.rccm') }} : {{ $institution->rccm }}@endif
                @if($institution?->id_nat)
                    {{ ($institution?->tax_id || $institution?->rccm) ? ' | ' : '' }}{{ __('messages.id_nat') }} : {{ $institution->id_nat }}
                @endif
            </div>
        </td>
        <td class="ei-right">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" class="ei-logo" alt="">
            @endif
            <div class="ei-titre">{{ $title }}</div>
            <div class="ei-sous-titre">{{ $sousTitre }}</div>
        </td>
    </tr>
</table>
