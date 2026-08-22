<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TwoFactorAuthController extends Controller
{
    public function showVerifyForm(Request $request)
    {
        if (! Session::has('2fa_pending_user_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor');
    }

    public function showSetup(Request $request)
    {
        $user = $request->user();
        $twoFactor = TwoFactorAuth::firstOrCreate(
            ['user_id' => $user->id, 'method' => 'totp'],
            ['is_enabled' => false]
        );

        if (! $twoFactor->secret) {
            $twoFactor->generateTotpSecret();
        }

        $qrCodeUrl = $twoFactor->getQrCodeUrl();
        $secret = $twoFactor->getTotpSecret();

        return view('admin.profile.2fa-setup', compact('twoFactor', 'qrCodeUrl', 'secret'));
    }

    public function enable(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)
            ->where('method', 'totp')
            ->first();

        if (! $twoFactor || ! $twoFactor->verifyCode($validated['code'])) {
            return back()->withErrors(['code' => 'The verification code is invalid. Please try again.']);
        }

        $twoFactor->update([
            'is_enabled' => true,
            'enabled_at' => now(),
        ]);

        $backupCodes = $twoFactor->generateBackupCodes();

        return view('admin.profile.2fa-recovery', compact('backupCodes'));
    }

    public function disable(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();
        TwoFactorAuth::disableForUser($user);

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $userId = Session::get('2fa_pending_user_id');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Session expired. Please login again.']);
        }

        $twoFactor = TwoFactorAuth::where('user_id', $user->id)
            ->where('method', 'totp')
            ->where('is_enabled', true)
            ->first();

        if (! $twoFactor) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => '2FA is not configured for this account.']);
        }

        $isValid = strlen($validated['code']) === 6
            ? $twoFactor->verifyCode($validated['code'])
            : $twoFactor->useBackupCode($validated['code']);

        if (! $isValid) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        Session::forget('2fa_pending_user_id');
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('admin_last_activity', now()->timestamp);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function regenerateCodes(Request $request)
    {
        $user = $request->user();
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)
            ->where('method', 'totp')
            ->where('is_enabled', true)
            ->first();

        if (! $twoFactor) {
            return back()->withErrors(['error' => '2FA is not enabled.']);
        }

        $validated = $request->validate([
            'password' => 'required|current_password',
        ]);

        $backupCodes = $twoFactor->generateBackupCodes();

        return view('admin.profile.2fa-recovery', compact('backupCodes'));
    }
}
