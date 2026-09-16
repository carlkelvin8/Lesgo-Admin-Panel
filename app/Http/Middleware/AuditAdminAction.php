<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Services\AuditActionResolver;
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
        $resource = $this->resolveResource($request);
        $oldSnapshot = $this->shouldCaptureOldValues($request) && $resource instanceof Model
            ? $resource->getAttributes()
            : null;

        $response = $next($request);

        if ($request->isMethodSafe() || $response->getStatusCode() >= 400) {
            return $response;
        }

        try {
            $route = $request->route();
            $action = $route?->getName() ?? $request->method().' '.$request->path();
            $newValues = $request->except([
                '_token', '_method', 'password', 'password_confirmation', 'two_factor_secret',
            ]);

            $resolved = app(AuditActionResolver::class)->resolve(
                $request, $resource, $newValues, $oldSnapshot, $action
            );

            AuditLog::create([
                'user_id' => $userId,
                'event_type' => 'admin_action',
                'event_category' => $resolved['event_category'],
                'action' => $action,
                'description' => $resolved['description'],
                'resource_type' => $resource ? class_basename($resource) : $request->segment(2),
                'resource_id' => $resource?->getKey(),
                'old_values' => $oldSnapshot,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'request_id' => $request->header('X-Request-ID'),
                'risk_level' => $resolved['risk_level'],
                'is_suspicious' => false,
                'context' => ['method' => $request->method(), 'path' => $request->path()],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    protected function resolveResource(Request $request): ?Model
    {
        $route = $request->route();

        return collect($route?->parameters() ?? [])
            ->first(fn ($value) => $value instanceof Model) ?: null;
    }

    protected function shouldCaptureOldValues(Request $request): bool
    {
        return in_array($request->method(), ['PUT', 'PATCH'], true);
    }
}