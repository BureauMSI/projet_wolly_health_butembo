@php
    $city = $institution?->city ?: __('messages.name');
    $done = __('messages.report_done_at', [
        'city' => $city,
        'date' => ($printedAt ?? now())->format('d/m/Y'),
    ]);
@endphp
<p class="pied">{{ $done }}
    @if(!empty($printedBy)) · {{ __('messages.printed_by') }} : {{ $printedBy }}@endif
    @if($institution?->invoice_footer)<br>{{ $institution->invoice_footer }}@endif
</p>
<table class="signatures">
    <tr>
        <td>
            <div>{{ ($audience ?? '') === 'member' ? __('messages.report_sign_member') : __('messages.report_sign_left') }}</div>
            <div class="ligne">{{ __('messages.report_signature') }}</div>
        </td>
        <td>
            <div>{{ __('messages.report_sign_right') }}</div>
            <div class="ligne">{{ __('messages.report_signature') }}</div>
        </td>
    </tr>
</table>
