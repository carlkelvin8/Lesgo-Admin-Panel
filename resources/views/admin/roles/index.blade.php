@extends('admin.layouts.app')
@section('title', 'Roles & Permissions - LesGo Admin')
@section('header', 'Roles & Permissions')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-white p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Administrator access control</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-600">Choose which admin modules each access level can use. Assign an access level to an administrator from the Users page.</p>
            </div>
            <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-purple-200 bg-white px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-50">
                <i class="fas fa-users"></i> View administrators
            </a>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach($roles as $role)
            @php
                $isFullAccess = in_array('*', $role->permissions ?? [], true);
                $assignedUsers = $roleCounts->get($role->key, 0);
            @endphp
            <article class="flex min-h-64 flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $role->is_protected ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                        <i class="fas {{ $role->is_protected ? 'fa-crown' : 'fa-user-shield' }}"></i>
                    </span>
                    @if($role->is_protected)
                        <span class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700">Protected</span>
                    @endif
                </div>

                <h4 class="mt-4 text-base font-semibold text-gray-900">{{ $role->label }}</h4>
                <p class="mt-1 text-sm text-gray-500">{{ $assignedUsers }} {{ Str::plural('administrator', $assignedUsers) }} assigned</p>

                <div class="mt-5 border-t border-gray-100 pt-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Access</p>
                    <p class="mt-1 text-sm font-medium text-gray-700">
                        {{ $isFullAccess ? 'All permissions' : count($role->permissions ?? []).' of '.$permissionCount.' permissions' }}
                    </p>
                </div>

                <div class="mt-auto pt-5">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium {{ $role->is_protected ? 'border border-gray-200 text-gray-700 hover:bg-gray-50' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                        <i class="fas {{ $role->is_protected ? 'fa-eye' : 'fa-pen' }}"></i>
                        {{ $role->is_protected ? 'View permissions' : 'Edit permissions' }}
                    </a>
                </div>
            </article>
        @endforeach
    </div>
</div>
@endsection
