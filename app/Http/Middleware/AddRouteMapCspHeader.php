<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddRouteMapCspHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $tileHosts = (array) config('map.tile_hosts', []);
        $imgHosts = implode(' ', array_map(static fn (string $host): string => 'https://'.$host, $tileHosts));

        $directives = [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: {$imgHosts}",
            "connect-src 'self' {$imgHosts}",
            "font-src 'self' data:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $directives));
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
