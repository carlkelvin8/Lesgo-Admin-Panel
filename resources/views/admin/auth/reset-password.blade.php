@extends('admin.layouts.auth')

@section('title', 'Choose New Password - LesGo Admin')

@section('content')
    <div class="mb-8">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Account recovery</p>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900">Choose a new password</h2>
        <p class="mt-3 text-sm leading-relaxed text-gray-500">Use at least eight characters with both letters and numbers.</p>
    </div>

    <form method="POST" action="{{ route('admin.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-5">
            <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Admin email address</label>
            <div class="auth-form-control @error('email') has-error @enderror">
                <span class="auth-field-icon"><i class="fas fa-envelope"></i></span>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="auth-input">
            </div>
            @error('email')<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="mb-5">
            <label for="password" class="mb-2 block text-sm font-semibold text-gray-700">New password</label>
            <div class="auth-form-control @error('password') has-error @enderror">
                <span class="auth-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="auth-input auth-input-password" placeholder="Create a strong password">
                <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show new password"><i class="fas fa-eye"></i></button>
            </div>
            @error('password')<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="mb-7">
            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-gray-700">Confirm new password</label>
            <div class="auth-form-control">
                <span class="auth-field-icon"><i class="fas fa-shield-halved"></i></span>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="auth-input auth-input-password" placeholder="Repeat your new password">
                <button type="button" class="auth-password-toggle" data-password-toggle="password_confirmation" aria-label="Show confirmed password"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <button type="submit" class="brand-button flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 font-semibold">
            <i class="fas fa-key"></i> Reset password
        </button>
    </form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.querySelector('i').className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
            input.focus();
        });
    });
</script>
@endpush
