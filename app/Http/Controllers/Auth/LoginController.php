<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember)) {
            Auth::guard('member')->logout();
            $request->session()->regenerate();
            $request->session()->put('locale', Auth::guard('web')->user()->locale);

            return redirect()->intended(route('admin.dashboard'));
        }

        if (Auth::guard('member')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'status' => 'active',
        ])) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put('locale', Auth::guard('member')->user()->locale);

            return redirect()->intended(route('member.dashboard'));
        }

        return back()->withErrors([
            'username' => __('messages.login_failed'),
        ])->onlyInput('username');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
