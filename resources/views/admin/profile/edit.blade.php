@extends('admin.layouts.app')
@section('title', 'My Profile - LesGo Admin')
@section('header', 'My Administrator Profile')

@section('content')
<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-lg font-bold text-purple-700">{{ strtoupper(substr($admin->name, 0, 1)) }}</div>
            <div><h3 class="font-semibold text-gray-900">Account details</h3><p class="text-sm text-gray-500">{{ $admin->adminRoleLabel() }}</p></div>
        </div>
        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <div><label class="mb-1 block text-sm font-medium">Name</label><input name="name" value="{{ old('name', $admin->name) }}" required class="w-full rounded-lg border px-3 py-2"></div>
            <div><label class="mb-1 block text-sm font-medium">Email</label><input type="email" name="email" value="{{ old('email', $admin->email) }}" required class="w-full rounded-lg border px-3 py-2"></div>
            <div><label class="mb-1 block text-sm font-medium">Phone number</label><input name="phone_number" value="{{ old('phone_number', $admin->phone_number) }}" class="w-full rounded-lg border px-3 py-2"></div>
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Save profile</button>
        </form>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Change password</h3>
        <p class="mt-1 text-sm text-gray-500">Changing your password signs out your other admin sessions.</p>
        <form method="POST" action="{{ route('admin.profile.password') }}" class="mt-5 space-y-4">
            @csrf @method('PUT')
            <div><label class="mb-1 block text-sm font-medium">Current password</label><input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-lg border px-3 py-2"></div>
            <div><label class="mb-1 block text-sm font-medium">New password</label><input type="password" name="password" required autocomplete="new-password" class="w-full rounded-lg border px-3 py-2"><p class="mt-1 text-xs text-gray-500">At least 10 characters with uppercase, lowercase, and a number.</p></div>
            <div><label class="mb-1 block text-sm font-medium">Confirm new password</label><input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-lg border px-3 py-2"></div>
            <button class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white">Update password</button>
        </form>
    </section>
</div>

<!-- Two-Factor Authentication -->
<section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-shield-halved text-purple-600"></i> Two-Factor Authentication
            </h3>
            <p class="mt-1 text-sm text-gray-500">Add an extra layer of security to your admin account with TOTP-based two-factor authentication.</p>
        </div>
        @php
            $twoFactor = $admin->twoFactorAuth;
            $isEnabled = $twoFactor && $twoFactor->is_enabled;
        @endphp
        @if($isEnabled)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                <i class="fas fa-check-circle"></i> Enabled
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                <i class="fas fa-circle-xmark"></i> Disabled
            </span>
        @endif
    </div>
    <div class="mt-4">
        @if($isEnabled)
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('admin.2fa.disable') }}" x-data onsubmit="return confirm('Are you sure you want to disable two-factor authentication?')">
                    @csrf
                    <input type="hidden" name="password" value="">
                    <button type="button" @click="$el.closest('form').querySelector('input[name=password]').value = prompt('Enter your current password to confirm:') || ''; if($el.closest('form').querySelector('input[name=password]').value) $el.closest('form').submit()"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition">
                        <i class="fas fa-ban mr-1"></i> Disable 2FA
                    </button>
                </form>
                <a href="{{ route('admin.2fa.setup') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
                    <i class="fas fa-rotate mr-1"></i> Regenerate Codes
                </a>
            </div>
        @else
            <a href="{{ route('admin.2fa.setup') }}" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition">
                <i class="fas fa-shield-halved"></i> Set Up Two-Factor Authentication
            </a>
        @endif
    </div>
</section>

<section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="border-b px-6 py-4"><h3 class="font-semibold text-gray-900">Active administrator sessions</h3><p class="mt-1 text-sm text-gray-500">Review and revoke database-backed browser sessions.</p></div>
    <div class="divide-y">
        @forelse($sessions as $session)
            <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0"><p class="truncate text-sm font-medium text-gray-800">{{ $session->user_agent ?: 'Unknown browser' }}</p><p class="mt-1 text-xs text-gray-500">{{ $session->ip_address ?: 'Unknown IP' }} · {{ $session->last_activity->diffForHumans() }} @if($session->is_current) · <span class="font-semibold text-green-600">Current session</span>@endif</p></div>
                @unless($session->is_current)
                    <form method="POST" action="{{ route('admin.profile.sessions.destroy', $session->id) }}" onsubmit="return confirm('Sign out this administrator session?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600">Sign out</button></form>
                @endunless
            </div>
        @empty
            <p class="px-6 py-8 text-center text-sm text-gray-500">No database-backed sessions were found.</p>
        @endforelse
    </div>
</section>
@endsection
