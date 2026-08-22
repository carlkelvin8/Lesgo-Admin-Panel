@extends('admin.layouts.app')
@section('title', 'Recovery Codes - LesGo Admin')
@section('header', 'Two-Factor Recovery Codes')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-key text-yellow-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Save Your Recovery Codes</h3>
            <p class="text-sm text-gray-500 mt-1">Store these codes in a safe place. Each code can only be used once if you lose access to your authenticator.</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6" x-data="{ copied: false }">
            <div class="grid grid-cols-2 gap-2">
                @foreach($backupCodes as $code)
                    <div class="bg-white border rounded px-3 py-2 text-center font-mono text-sm">{{ $code }}</div>
                @endforeach
            </div>
            <button @click="
                    navigator.clipboard.writeText('{{ implode('\\n', $backupCodes) }}');
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
                class="mt-4 w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm flex items-center justify-center gap-2 transition">
                <i class="fas" :class="copied ? 'fa-check text-green-600' : 'fa-copy'"></i>
                <span x-text="copied ? 'Copied!' : 'Copy All Codes'"></span>
            </button>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-medium text-yellow-800">Important</p>
                    <p class="text-yellow-700">These codes will not be shown again. Download or print them now.</p>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.profile.edit') }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-sm font-medium transition">
            <i class="fas fa-check mr-1"></i> I've Saved My Codes
        </a>
    </div>
</div>
@endsection
