<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('sync.token', '');

        if ($expected === '') {
            return response()->json([
                'message' => __('messages.sync_token_required'),
            ], 503);
        }

        $provided = (string) $request->header('X-Sync-Token', '');

        if (! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => __('messages.sync_unauthorized'),
            ], 401);
        }

        return $next($request);
    }
}
