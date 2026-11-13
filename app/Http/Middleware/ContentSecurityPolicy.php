<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response->headers->has('Content-Security-Policy')) {
            return $response;
        }

        $devOrigin = '';
        $devSocket = '';
        if (app()->environment('local') && Vite::isRunningHot()) {
            $url = trim(file_get_contents(Vite::hotFile()));
            $parts = parse_url($url);
            if (is_array($parts) && in_array($parts['scheme'] ?? '', ['http', 'https'], true) && isset($parts['host'])) {
                $origin = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                $devOrigin = ' '.$parts['scheme'].'://'.$origin;
                $devSocket = ' '.($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$origin;
            }
        }

        $cspDirectives = [
            "default-src 'self'",
            // The standard Alpine build evaluates the expressions used by the Blade views.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com".$devOrigin,
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com".$devOrigin,
            "img-src 'self' data: https:",
            "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com".$devOrigin,
            "connect-src 'self' https://ip-api.com".$devOrigin.$devSocket,
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
