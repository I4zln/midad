<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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

        // A fresh nonce per response lets the few inline scripts run under a strict Content-Security-Policy.
        $nonce = base64_encode(random_bytes(16));
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // Everything is self-hosted, so the policy only allows this origin. Skipped while the Vite dev server is running.
        if (! Vite::isRunningHot()) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self'",
                "img-src 'self' data: blob:",
                "font-src 'self'",
                "media-src 'self'",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]));
        }

        // Do not advertise the PHP version (php.ini expose_php does the same on the host; this covers any other server).
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');
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
