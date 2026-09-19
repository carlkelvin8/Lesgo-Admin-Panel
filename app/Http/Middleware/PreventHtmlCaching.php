<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Never let a browser (or intermediary) serve a stale cached copy of an admin
 * page. Without this, an old HTML snapshot can survive on the client and keep
 * producing broken/outdated links (e.g. URLs wrapped in literal quotes) even
 * after the server has long been fixed.
 */
class PreventHtmlCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}