<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Auth\LoginController as UnifiedLoginController;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function store(Request $request): RedirectResponse
    {
        return app(UnifiedLoginController::class)->store($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
