@extends('admin.layouts.app')
@section('title', 'System Health - LesGo Admin')
@section('header', 'System Health')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($checks as $check)
            <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $check['status'] === 'healthy' ? 'border-green-500' : ($check['status'] === 'warning' ? 'border-yellow-500' : 'border-red-500') }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $check['status'] === 'healthy' ? 'bg-green-100' : ($check['status'] === 'warning' ? 'bg-yellow-100' : 'bg-red-100') }}">
                        <i class="fas {{ $check['status'] === 'healthy' ? 'fa-check text-green-600' : ($check['status'] === 'warning' ? 'fa-exclamation text-yellow-600' : 'fa-times text-red-600') }}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $check['name'] }}</h3>
                        <p class="text-sm text-gray-500">{{ $check['message'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <x-card>
        <x-slot name="header">
            <h3 class="text-lg font-semibold text-gray-900">Server Information</h3>
        </x-slot>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">PHP Version</span><span class="font-medium">{{ PHP_VERSION }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Laravel Version</span><span class="font-medium">{{ app()->version() }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Server Software</span><span class="font-medium">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Memory Limit</span><span class="font-medium">{{ ini_get('memory_limit') }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Max Execution Time</span><span class="font-medium">{{ ini_get('max_execution_time') }}s</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Upload Max Filesize</span><span class="font-medium">{{ ini_get('upload_max_filesize') }}</span></div>
        </div>
    </x-card>
</div>
@endsection
