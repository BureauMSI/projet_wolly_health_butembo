@php
    $fiche = $member ?? $subjectMember;
    $genderLabel = $fiche->gender ? __('messages.'.$fiche->gender) : '—';
@endphp
<table class="fiche-id">
    <tr>
        <td>
            <table class="data kv">
                <thead>
                    <tr><th colspan="2">{{ __('messages.sheet_identity') }}</th></tr>
                </thead>
                <tbody>
                    <tr><td class="text-start">{{ __('messages.member_code') }}</td><td class="text-start">{{ $fiche->member_code }}</td></tr>
                    <tr><td class="text-start">{{ __('messages.full_name') }}</td><td class="text-start">{{ $fiche->full_name }}</td></tr>
                    <tr><td class="text-start">{{ __('messages.gender') }}</td><td class="text-start">{{ $genderLabel }}</td></tr>
                    <tr><td class="text-start">{{ __('messages.birth_date') }}</td><td class="text-start">{{ $fiche->birth_date?->format('d/m/Y') ?: '—' }}</td></tr>
                    <tr><td class="text-start">{{ __('messages.phone') }}</td><td class="text-start">{{ $fiche->phone ?: '—' }}</td></tr>
                    <tr><td class="text-start">{{ __('messages.address') }}</td><td class="text-start">{{ $fiche->address ?: '—' }}</td></tr>
                    <tr>
                        <td class="text-start">{{ __('messages.sheet_amount_paid') }}</td>
                        <td class="text-start">{{ number_format((float) $fiche->membership_amount_usd, 2) }} USD</td>
                    </tr>
                </tbody>
            </table>
            <table class="data kv" style="margin-top:8px;">
                <thead>
                    <tr><th colspan="2">{{ __('messages.sheet_login') }}</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start">{{ __('messages.username') }}</td>
                        <td class="text-start login-value">{{ $fiche->username }}</td>
                    </tr>
                    <tr>
                        <td class="text-start">{{ __('messages.password') }}</td>
                        <td class="text-start login-value">
                            @if($loginPassword)
                                {{ $loginPassword }}
                            @else
                                {{ __('messages.sheet_password_personal') }}
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td class="fiche-photo-cell">
            @if($photoSrc)
                <img src="{{ $photoSrc }}" alt="" class="fiche-photo">
            @else
                <div class="fiche-photo fiche-photo-empty">{{ mb_strtoupper(mb_substr($fiche->full_name, 0, 1)) }}</div>
            @endif
        </td>
    </tr>
</table>

<h2 class="sec">{{ __('messages.sheet_guide_access_title') }}</h2>
<div class="fiche-guide">
    <p>{{ __('messages.sheet_guide_access_p1', [
        'url' => $portalUrl,
        'username' => $fiche->username,
        'password' => $loginPassword ?: __('messages.sheet_password_personal'),
    ]) }}</p>
    <p>{{ __('messages.sheet_guide_access_p2') }}</p>
</div>

<h2 class="sec">{{ __('messages.sheet_guide_how_title') }}</h2>
<div class="fiche-guide">
    <p>{{ __('messages.sheet_guide_how_p1') }}</p>
    <p>{{ __('messages.sheet_guide_how_p2') }}</p>
</div>

<h2 class="sec">{{ __('messages.sheet_guide_phone_title') }}</h2>
<div class="fiche-guide">
    <p>{{ __('messages.sheet_guide_phone_p1') }}</p>
    <p>{{ __('messages.sheet_guide_phone_p2') }}</p>
    <p>{{ __('messages.sheet_guide_phone_p3') }}</p>
</div>
