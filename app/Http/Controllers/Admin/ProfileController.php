<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordHistory;
use App\Services\SessionAnomalyDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $sessions = collect();

        if (Schema::hasTable('sessions')) {
            $sessions = DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn ($session) => (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => now()->setTimestamp($session->last_activity),
                    'is_current' => hash_equals($request->session()->getId(), $session->id),
                ]);
        }

        return view('admin.profile.edit', [
            'admin' => $request->user(),
            'sessions' => $sessions,
        ]);
    }

    public function update(Request $request)
    {
        $admin = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($admin->id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $admin->update($validated);

        return back()->with('success', 'Your administrator profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $admin = $request->user();

        if (PasswordHistory::isPasswordReused($admin->id, $validated['password'])) {
            return back()->withErrors(['password' => 'You cannot reuse a recent password. Please choose a different one.'])->withInput();
        }

        $admin->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'remember_token' => null,
        ])->save();

        PasswordHistory::recordPassword($admin->id, $validated['password']);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $admin->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $request->session()->regenerate();

        return back()->with('success', 'Password changed and other administrator sessions were signed out.');
    }

    public function destroySession(Request $request, string $sessionId)
    {
        if (hash_equals($request->session()->getId(), $sessionId)) {
            return back()->withErrors(['session' => 'Use Log out to end your current session.']);
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', $request->user()->id)
                ->delete();
        }

        return back()->with('success', 'The selected administrator session was signed out.');
    }
}
