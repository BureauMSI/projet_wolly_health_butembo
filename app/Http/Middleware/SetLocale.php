<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['fr', 'sw'];
        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? $request->user('member')?->locale
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = 'fr';
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        if ($user = $request->user('web')) {
            $user->loadMissing('branch');
        }

        return $next($request);
    }
}
