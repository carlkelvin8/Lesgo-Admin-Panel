@extends('admin.layouts.app')
@section('title', 'My Profile - LesGo Admin')
@section('header', 'My Administrator Profile')

@section('content')
<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-4">
            @if($admin->profile_picture)
                <img src="{{ \Illuminate\Support\Str::startsWith($admin->profile_picture, ['http://', 'https://']) ? $admin->profile_picture : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($admin->profile_picture) }}" alt="{{ $admin->name }}" class="h-12 w-12 rounded-full border object-cover">
            @else
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-lg font-bold text-purple-700">{{ strtoupper(substr($admin->name, 0, 1)) }}</div>
            @endif
            <div><h3 class="font-semibold text-gray-900">Account details</h3><p class="text-sm text-gray-500">{{ $admin->adminRoleLabel() }}</p></div>
        </div>
        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label for="profile_picture" class="mb-1 block text-sm font-medium">Profile picture</label>
                <input id="profile_picture" type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP up to 2 MB.</p>
                @if($admin->profile_picture)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remove_profile_picture" value="1"> Remove current picture</label>
                @endif
                @error('profile_picture')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div><label for="profile-name" class="mb-1 block text-sm font-medium">Name</label><input name="name" id="profile-name" value="{{ old('name', $admin->name) }}" required class="w-full rounded-lg border px-3 py-2"></div>
            <div><label for="profile-email" class="mb-1 block text-sm font-medium">Email</label><input type="email" name="email" id="profile-email" value="{{ old('email', $admin->email) }}" required class="w-full rounded-lg border px-3 py-2"></div>
            <div><label for="profile-phone_number" class="mb-1 block text-sm font-medium">Phone number</label><input name="phone_number" id="profile-phone_number" value="{{ old('phone_number', $admin->phone_number) }}" class="w-full rounded-lg border px-3 py-2"></div>
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Save profile</button>
        </form>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Change password</h3>
        <p class="mt-1 text-sm text-gray-500">Changing your password signs out your other admin sessions.</p>
        <form method="POST" action="{{ route('admin.profile.password') }}" class="mt-5 space-y-4">
            @csrf @method('PUT')
            <div><label for="profile-current_password" class="mb-1 block text-sm font-medium">Current password</label><input type="password" name="current_password" id="profile-current_password" required autocomplete="current-password" class="w-full rounded-lg border px-3 py-2"></div>
            <div><label for="profile-password" class="mb-1 block text-sm font-medium">New password</label><input type="password" name="password" id="profile-password" required autocomplete="new-password" class="w-full rounded-lg border px-3 py-2"><p class="mt-1 text-xs text-gray-500">At least 10 characters with uppercase, lowercase, and a number.</p></div>
            <div><label for="profile-password_confirmation" class="mb-1 block text-sm font-medium">Confirm new password</label><input type="password" name="password_confirmation" id="profile-password_confirmation" required autocomplete="new-password" class="w-full rounded-lg border px-3 py-2"></div>
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
            <form method="POST" action="{{ route('admin.2fa.regenerate') }}" class="space-y-3">
                @csrf
                <label for="two-factor-password" class="block text-sm font-medium">Current password</label>
                <input id="two-factor-password" type="password" name="password" required autocomplete="current-password" class="w-full rounded-lg border px-3 py-2">
                <div class="flex flex-wrap gap-3">
                    <button class="rounded-lg bg-gray-200 px-4 py-2 text-sm text-gray-700">Regenerate Codes</button>
                    <button formaction="{{ route('admin.2fa.disable') }}" onclick="return confirm('Disable two-factor authentication?')" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Disable 2FA</button>
                </div>
            </form>
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
                    <button type="button" class="text-sm font-semibold text-red-600" x-data x-on:click="$dispatch('confirm-modal', { title: 'Sign Out Session', message: 'Sign out this administrator session?', confirmText: 'Sign Out', onConfirm: () => { const f = document.createElement('form'); f.method = 'POST'; f.action = '{{ route('admin.profile.sessions.destroy', $session->id) }}'; const i1=document.createElement('input');i1.type='hidden';i1.name='_token';i1.value='{{ csrf_token() }}';f.appendChild(i1);const i2=document.createElement('input');i2.type='hidden';i2.name='_method';i2.value='DELETE';f.appendChild(i2); document.body.appendChild(f); f.submit(); } })">Sign out</button>
                @endunless
            </div>
        @empty
            <x-empty-state icon="fa-users" title="No sessions found" description="No database-backed sessions were found." />
        @endforelse
    </div>
</section>
@endsection
