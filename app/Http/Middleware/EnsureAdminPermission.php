<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || ! $user->hasAdminPermission($permission)) {
            abort(403, 'Your admin role does not have permission to perform this action.');
        }

        return $next($request);
    }
}
