@extends('admin.layouts.auth')

@section('title', 'Sign In - LesGo Admin')

@section('content')
    <div class="mb-8">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admin portal</p>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900">Welcome back</h2>
        <p class="mt-2 text-sm leading-relaxed text-gray-500">Sign in with your administrator account to continue.</p>
    </div>

    @if(session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
            <i class="fas fa-circle-check mt-0.5 text-green-600"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form id="login-form" method="POST" action="{{ route('admin.login.post') }}">
        @csrf

        <div class="mb-5">
            <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Email address</label>
            <div class="auth-form-control @error('email') has-error @enderror">
                <span class="auth-field-icon"><i class="fas fa-envelope"></i></span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" inputmode="email"
                       class="auth-input" placeholder="admin@lesgo.com"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')
                <p id="email-error" class="mt-2 flex items-center gap-1.5 text-xs text-red-600" role="alert"><i class="fas fa-circle-exclamation"></i>{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3">
            <div class="mb-2 flex items-center justify-between gap-3">
                <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                <a href="{{ route('admin.password.request') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Forgot password?</a>
            </div>
            <div class="auth-form-control @error('password') has-error @enderror">
                <span class="auth-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="auth-input auth-input-password" placeholder="Enter your password"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button id="password-toggle" type="button" class="auth-password-toggle" aria-label="Show password" aria-controls="password">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="mt-2 flex items-center gap-1.5 text-xs text-red-600" role="alert"><i class="fas fa-circle-exclamation"></i>{{ $message }}</p>
            @enderror
            <p id="caps-lock-warning" class="mt-2 hidden items-center gap-1.5 text-xs text-amber-700" role="status">
                <i class="fas fa-arrow-up-long"></i> Caps Lock is on
            </p>
        </div>

        <label class="mb-6 inline-flex cursor-pointer items-center gap-2.5 text-sm text-gray-600">
            <input type="checkbox" name="remember" value="1" class="auth-checkbox" {{ old('remember') ? 'checked' : '' }}>
            <span>Keep me signed in on this device</span>
        </label>

        <button id="login-submit" type="submit" class="brand-button flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 font-semibold">
            <i id="login-spinner" class="fas fa-circle-notch hidden animate-spin" aria-hidden="true"></i>
            <i id="login-icon" class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i>
            <span id="login-label">Sign in securely</span>
        </button>
    </form>

    <div class="mt-7 flex items-center justify-center gap-2 text-xs text-gray-400">
        <i class="fas fa-shield-halved text-blue-500"></i>
        <span>Your session is encrypted and monitored.</span>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('login-form');
        const password = document.getElementById('password');
        const toggle = document.getElementById('password-toggle');
        const capsWarning = document.getElementById('caps-lock-warning');
        const submit = document.getElementById('login-submit');
        const spinner = document.getElementById('login-spinner');
        const icon = document.getElementById('login-icon');
        const label = document.getElementById('login-label');

        toggle?.addEventListener('click', () => {
            const showing = password.type === 'text';
            password.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            toggle.querySelector('i').className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
            password.focus();
        });

        const updateCapsLock = (event) => {
            const active = event.getModifierState && event.getModifierState('CapsLock');
            capsWarning.classList.toggle('hidden', !active);
            capsWarning.classList.toggle('flex', active);
        };

        password?.addEventListener('keydown', updateCapsLock);
        password?.addEventListener('keyup', updateCapsLock);
        password?.addEventListener('blur', () => {
            capsWarning.classList.add('hidden');
            capsWarning.classList.remove('flex');
        });

        form?.addEventListener('submit', () => {
            submit.disabled = true;
            submit.setAttribute('aria-busy', 'true');
            spinner.classList.remove('hidden');
            icon.classList.add('hidden');
            label.textContent = 'Signing in…';
        });
    })();
</script>
@endpush
