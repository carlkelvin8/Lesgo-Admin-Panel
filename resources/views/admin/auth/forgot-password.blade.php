@extends('admin.layouts.auth')

@section('title', 'Reset Password - LesGo Admin')

@section('content')
    <a href="{{ route('admin.login') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left text-xs"></i> Back to sign in
    </a>

    <div class="mb-8">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Account recovery</p>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900">Forgot your password?</h2>
        <p class="mt-3 text-sm leading-relaxed text-gray-500">Enter your admin email. If it matches an active account, we’ll send a secure reset link.</p>
    </div>

    @if(session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm leading-relaxed text-green-800" role="status">
            <i class="fas fa-paper-plane mt-0.5 text-green-600"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.password.email') }}">
        @csrf
        <div class="mb-6">
            <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Admin email address</label>
            <div class="auth-form-control @error('email') has-error @enderror">
                <span class="auth-field-icon"><i class="fas fa-envelope"></i></span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       class="auth-input" placeholder="admin@lesgo.com">
            </div>
            @error('email')
                <p class="mt-2 flex items-center gap-1.5 text-xs text-red-600" role="alert"><i class="fas fa-circle-exclamation"></i>{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="brand-button flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 font-semibold">
            <i class="fas fa-paper-plane"></i> Send reset link
        </button>
    </form>

    <p class="mt-7 text-center text-xs leading-relaxed text-gray-400">For security, we never confirm whether an email is registered.</p>
@endsection
