<?php

namespace App\Http\Middleware;

use App\Models\SecuritySetting;
use App\Services\AdminNetworkAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminMiddleware
{
    public function __construct(private readonly AdminNetworkAccess $networkAccess) {}

    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        if (! Auth::user()->isAdmin() || ! Auth::user()->is_active) {
            abort(403, 'Unauthorized. Admin access only.');
        }

        if (! $this->networkAccess->allows($request->ip())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Administrator access is not allowed from this network.');
        }

        $timeoutMinutes = $this->sessionTimeoutMinutes();
        $lastActivity = (int) $request->session()->get('admin_last_activity', 0);

        if ($lastActivity > 0 && now()->timestamp - $lastActivity > $timeoutMinutes * 60) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Your administrator session expired. Please sign in again.']);
        }

        $request->session()->put('admin_last_activity', now()->timestamp);

        return $next($request);
    }

    private function sessionTimeoutMinutes(): int
    {
        try {
            if (Schema::hasTable('security_settings')) {
                return max(5, min(1440, SecuritySetting::value('session_timeout_minutes', 480)));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return 480;
    }
}
