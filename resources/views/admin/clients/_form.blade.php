@php $client = $client ?? null; @endphp
<div class="col-md-6">
    <label class="form-label">{{ __('messages.name_field') }}</label>
    <input class="form-control" name="name" value="{{ old('name', $client->name ?? '') }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">{{ __('messages.phone') }}</label>
    <input class="form-control" name="phone" value="{{ old('phone', $client->phone ?? '') }}">
</div>
<div class="col-md-6">
    <label class="form-label">{{ __('messages.referrer') }}</label>
    <select class="form-select" name="referrer_member_id" required data-searchable="1">
        <option value="">{{ __('messages.select') }}</option>
        @foreach($members as $member)
            <option value="{{ $member->id }}" @selected(old('referrer_member_id', $client->referrer_member_id ?? '') == $member->id)>{{ $member->full_name }} ({{ $member->username }})</option>
        @endforeach
    </select>
</div>
