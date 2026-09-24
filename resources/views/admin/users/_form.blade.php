@php
    $userModel = $user ?? null;
    $selectedRole = old('role', $userModel->role ?? 'cashier');
@endphp
<div class="col-md-6">
    <label class="form-label">{{ __('messages.name_field') }}</label>
    <input class="form-control" name="name" value="{{ old('name', $userModel->name ?? '') }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">{{ __('messages.username') }}</label>
    <input class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username', $userModel->username ?? '') }}" required autocomplete="off">
    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-6">
    <label class="form-label">{{ __('messages.email') }}</label>
    <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $userModel->email ?? '') }}">
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-6">
    <label class="form-label">{{ __('messages.password') }}</label>
    <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" @unless($userModel) required @endunless autocomplete="new-password">
    @if($userModel)
        <div class="form-text">{{ __('messages.password_leave_blank') }}</div>
    @endif
    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">{{ __('messages.role') }}</label>
    <select class="form-select @error('role') is-invalid @enderror" name="role" id="user-role" data-no-search="1" required>
        @foreach($roles as $role)
            <option
                value="{{ $role['value'] }}"
                data-hint="{{ $role['hint'] }}"
                data-needs-branch="{{ $role['value'] === 'admin' ? '0' : '1' }}"
                @selected($selectedRole === $role['value'])
            >{{ $role['label'] }}</option>
        @endforeach
    </select>
    <div class="form-text" id="user-role-hint">{{ __('messages.roles_hint') }}</div>
    @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="col-md-4" id="user-branch-wrap">
    <label class="form-label">{{ __('messages.branch') }}</label>
    <select class="form-select @error('branch_id') is-invalid @enderror" name="branch_id" id="user-branch" data-searchable="1">
        <option value="">{{ __('messages.select') }}</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $userModel->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
        @endforeach
    </select>
    <div class="form-text">{{ __('messages.user_branch_required_hint') }}</div>
    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">{{ __('messages.locale') }}</label>
    <select class="form-select" name="locale" data-no-search="1" required>
        <option value="fr" @selected(old('locale', $userModel->locale ?? 'fr') === 'fr')>{{ __('messages.french') }}</option>
        <option value="sw" @selected(old('locale', $userModel->locale ?? '') === 'sw')>{{ __('messages.swahili') }}</option>
    </select>
</div>
<div class="col-12">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="user-active" value="1" @checked(old('is_active', $userModel->is_active ?? true))>
        <label class="form-check-label" for="user-active">{{ __('messages.active') }}</label>
    </div>
</div>

<ul class="col-12 list-unstyled small text-muted mb-0" id="user-role-cards">
    @foreach($roles as $role)
        <li class="mb-1"><strong>{{ $role['label'] }}</strong> — {{ $role['hint'] }}</li>
    @endforeach
</ul>
