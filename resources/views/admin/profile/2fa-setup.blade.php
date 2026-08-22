@extends('admin.layouts.app')
@section('title', 'Setup Two-Factor Authentication - LesGo Admin')
@section('header', 'Setup Two-Factor Authentication')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-halved text-green-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Scan QR Code</h3>
            <p class="text-sm text-gray-500 mt-1">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>
        </div>

        <div class="flex justify-center mb-6">
            <div class="bg-white p-4 rounded-xl border-2 border-gray-200">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="2FA QR Code" class="w-48 h-48">
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <p class="text-xs text-gray-500 mb-2">Or enter this secret manually:</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 bg-white border rounded px-3 py-2 text-sm font-mono text-gray-800 select-all">{{ $secret }}</code>
                <button onclick="navigator.clipboard.writeText('{{ $secret }}')" class="text-gray-500 hover:text-gray-700" title="Copy">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.2fa.enable') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <input type="text" name="code" required maxlength="6" pattern="[0-9]{6}" placeholder="Enter the 6-digit code"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-lg tracking-[0.5em] font-mono focus:border-blue-500 outline-none transition" autofocus>
                @error('code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full brand-button px-4 py-3 rounded-lg text-sm font-medium">
                <i class="fas fa-check mr-1"></i> Enable Two-Factor Authentication
            </button>
        </form>
    </div>

    <div class="text-center">
        <a href="{{ route('admin.profile.edit') }}" class="text-gray-500 hover:text-gray-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Profile
        </a>
    </div>
</div>
@endsection
