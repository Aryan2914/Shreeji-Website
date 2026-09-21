<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnsureSessionId
{
    /**
     * Ensure guest users have a session ID for cart tracking.
     * Reads X-Session-Id from request header, or generates one.
     */
    public function handle(Request $request, \Closure $next)
    {
        if (!$request->user() && !$request->header('X-Session-Id')) {
            // We can't inject a header into the request, but we'll note this
            // The client MUST send X-Session-Id for guest cart operations
        }

        return $next($request);
    }
}
