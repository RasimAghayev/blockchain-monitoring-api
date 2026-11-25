<?php

namespace App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        // Content-Security-Policy is deliberately not set here — it is owned
        // exclusively by ContentSecurityPolicyMiddleware, which needs a looser
        // policy for the Swagger UI route (api/documentation) than for every
        // other response. Two middlewares both calling headers->set() for the
        // same header is a last-writer-wins bug waiting to happen; one owner.
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        return $response;
    }
}
