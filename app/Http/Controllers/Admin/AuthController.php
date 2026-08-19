<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['email' => "Too many login attempts. Try again in {$seconds} seconds."])
                ->onlyInput('email');
        }

        if (Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $user = Auth::user();
            if (! $user->isAdmin()) {
                Auth::logout();
                $this->recordFailedLogin($request, $credentials['email'], $throttleKey);

                return back()->withErrors(['email' => 'You do not have admin access.']);
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        $this->recordFailedLogin($request, $credentials['email'], $throttleKey);

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function showForgotPassword()
    {
        return view('admin.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $adminExists = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->exists();

        if ($adminExists) {
            Password::sendResetLink(['email' => $validated['email']]);
        }

        return back()->with('status', 'If an active administrator account matches that email, a password reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $isActiveAdmin = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->exists();

        if (! $isActiveAdmin) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.'])
                ->withInput($request->only('email'));
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])
                ->withInput($request->only('email'));
        }

        return redirect()->route('admin.login')->with('status', 'Your password has been reset. You can now sign in.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function recordFailedLogin(Request $request, string $email, string $throttleKey): void
    {
        RateLimiter::hit($throttleKey, 300);

        DB::table('failed_login_attempts')->insert([
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempted_at' => now(),
        ]);

        if (RateLimiter::attempts($throttleKey) === 5) {
            SecurityEvent::create([
                'event_type' => 'repeated_failed_admin_login',
                'severity' => 'high',
                'source' => 'admin_login',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "Five failed admin login attempts were recorded for {$email}.",
                'event_data' => ['email' => $email, 'attempts' => 5],
                'detected_at' => now(),
            ]);
        }
    }
}
