<form method="post" action="{{ $action }}" class="auth-form">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="username">{{ __('messages.username') }}</label>
        <div class="input-group member-input-icon">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input id="username" class="form-control @error('username') is-invalid @enderror" type="text" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="off" autofocus required>
        </div>
        @error('username')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">{{ __('messages.password') }}</label>
        <div class="input-group member-input-icon">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" autocomplete="current-password" required>
            <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" data-show-label="{{ __('messages.show_password') }}" data-hide-label="{{ __('messages.hide_password') }}" aria-label="{{ __('messages.show_password') }}">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
        <label class="form-check-label" for="remember">{{ __('messages.remember_me') }}</label>
    </div>
    <button class="btn btn-success w-100 auth-submit" type="submit">
        <i class="bi bi-box-arrow-in-right"></i> {{ __('messages.sign_in') }}
    </button>
</form>
