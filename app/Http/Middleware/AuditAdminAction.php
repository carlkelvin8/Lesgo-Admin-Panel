<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditAdminAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = auth()->id();
        $response = $next($request);

        if ($request->isMethodSafe() || $response->getStatusCode() >= 400) {
            return $response;
        }

        try {
            $route = $request->route();
            $parameters = collect($route?->parameters() ?? []);
            $resource = $parameters->first(fn ($value) => $value instanceof Model);

            AuditLog::create([
                'user_id' => $userId,
                'event_type' => 'admin_action',
                'event_category' => 'administration',
                'action' => $route?->getName() ?? $request->method().' '.$request->path(),
                'resource_type' => $resource ? class_basename($resource) : $request->segment(2),
                'resource_id' => $resource?->getKey(),
                'new_values' => $request->except([
                    '_token', '_method', 'password', 'password_confirmation', 'two_factor_secret',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'request_id' => $request->header('X-Request-ID'),
                'risk_level' => $request->isMethod('delete') ? 'high' : 'low',
                'is_suspicious' => false,
                'context' => ['method' => $request->method(), 'path' => $request->path()],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
