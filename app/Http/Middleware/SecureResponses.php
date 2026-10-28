<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Production hardening: HTTPS-only (honours the proxy's X-Forwarded-Proto) and baseline security headers on every response.
class SecureResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $production = app()->environment('production');

        // The host's health checker probes /up over plain http inside its network; answer it directly.
        if ($production && ! $request->isSecure() && config('app.force_https') && ! $request->is('up')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($production && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
