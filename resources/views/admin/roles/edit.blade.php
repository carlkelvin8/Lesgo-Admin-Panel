@extends('admin.layouts.app')
@section('title', $adminRole->label.' Permissions - LesGo Admin')
@section('header', $adminRole->label.' Permissions')

@section('actions')
<a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900">
    <i class="fas fa-arrow-left"></i> Back to roles
</a>
@endsection

@section('content')
@php
    $selectedPermissions = old('permissions', $adminRole->permissions ?? []);
    $hasFullAccess = in_array('*', $selectedPermissions, true);
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $adminRole->is_protected ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                    <i class="fas {{ $adminRole->is_protected ? 'fa-crown' : 'fa-user-shield' }}"></i>
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $adminRole->label }}</h3>
                        @if($adminRole->is_protected)
                            <span class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700">Protected role</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Used by {{ $roleUserCount }} {{ Str::plural('administrator', $roleUserCount) }}</p>
                </div>
            </div>

            @unless($adminRole->is_protected)
                <button id="toggle-all-permissions" type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-check-double"></i> Select all
                </button>
            @endunless
        </div>
    </div>

    @if($adminRole->is_protected)
        <div class="flex items-start gap-3 rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-800">
            <i class="fas fa-lock mt-0.5"></i>
            <p>Super Admin always has full access. Its permissions are protected to make sure the system cannot lose its highest-level administrator.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.roles.update', $adminRole) }}" class="space-y-5">
        @csrf
        @method('PUT')

        @foreach($permissionGroups as $group => $permissions)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                    <h4 class="font-semibold text-gray-800">{{ $group }}</h4>
                </div>
                <div class="grid gap-px bg-gray-100 sm:grid-cols-2">
                    @foreach($permissions as $key => $permission)
                        @php
                            $isRequired = in_array($key, $requiredPermissions, true);
                            $isChecked = $hasFullAccess || in_array($key, $selectedPermissions, true) || $isRequired;
                            $isDisabled = $adminRole->is_protected || $isRequired;
                        @endphp
                        <label for="permission-{{ str_replace('.', '-', $key) }}" class="flex gap-3 bg-white p-5 {{ $isDisabled ? 'cursor-default' : 'cursor-pointer hover:bg-blue-50/40' }}">
                            <input
                                id="permission-{{ str_replace('.', '-', $key) }}"
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $key }}"
                                @checked($isChecked)
                                @disabled($isDisabled)
                                class="permission-checkbox mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            @if($isRequired && !$adminRole->is_protected)
                                <input type="hidden" name="permissions[]" value="{{ $key }}">
                            @endif
                            <span>
                                <span class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-800">
                                    {{ $permission['label'] }}
                                    @if($isRequired)
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">Required</span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-xs leading-relaxed text-gray-500">{{ $permission['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        @unless($adminRole->is_protected)
            <div class="sticky bottom-4 flex flex-col-reverse gap-3 rounded-xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    <i class="fas fa-save"></i> Save permissions
                </button>
            </div>
        @endunless
    </form>
</div>
@endsection

@unless($adminRole->is_protected)
@section('scripts')
<script>
    (() => {
        const button = document.getElementById('toggle-all-permissions');
        const checkboxes = [...document.querySelectorAll('.permission-checkbox:not(:disabled)')];

        const refreshButton = () => {
            const allSelected = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
            button.innerHTML = allSelected
                ? '<i class="fas fa-xmark"></i> Clear optional'
                : '<i class="fas fa-check-double"></i> Select all';
        };

        button?.addEventListener('click', () => {
            const shouldSelect = !checkboxes.every((checkbox) => checkbox.checked);
            checkboxes.forEach((checkbox) => checkbox.checked = shouldSelect);
            refreshButton();
        });

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshButton));
        refreshButton();
    })();
</script>
@endsection
@endunless
