@extends('admin.layouts.app')
@section('title', 'Users - LesGo Admin')
@section('header', 'Users Management')

@section('actions')
@if(auth()->user()->hasAdminPermission('users.manage'))
<a href="{{ route('admin.users.export', request()->query()) }}" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm"><i class="fas fa-download mr-1"></i> Export CSV</a>
<a href="{{ route('admin.users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add User</a>
@endif
@endsection

@section('content')
<!-- Filters -->
<x-filter-panel>
    <x-filter-input name="search" label="Search" placeholder="Name, email, phone..." />
    <x-filter-input name="role" label="Role" type="select" :options="['' => 'All Roles', 'customer' => 'Customer', 'driver' => 'Driver', 'partner' => 'Partner', 'admin' => 'Admin']" />
    <x-filter-input name="status" label="Status" type="select" :options="['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
</x-filter-panel>

<!-- Table -->
<form method="POST" action="{{ route('admin.users.bulk-destroy') }}"
    x-data="{ selected: [] }"
    x-on:submit="if (!confirm(`Permanently remove ${selected.length} selected user account(s) from the apps? Admin accounts are protected.`)) $event.preventDefault()">
    @csrf @method('DELETE')
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-3 border-b bg-gray-50 flex items-center justify-between gap-4">
        <span class="text-sm text-gray-600"><span x-text="selected.length"></span> selected</span>
        <button type="submit" :disabled="selected.length === 0" class="bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-trash mr-1"></i> Delete Selected</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm responsive-table">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 w-10"><input type="checkbox" aria-label="Select all users on this page" @change="selected = $event.target.checked ? @js($users->pluck('id')->map(fn ($id) => (string) $id)->values()) : []"></th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Phone</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Role</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Joined</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4"><input type="checkbox" name="ids[]" value="{{ $user->id }}" x-model="selected" aria-label="Select {{ $user->name }}"></td>
                        <td class="px-6 py-4" data-label="User">
                            <div class="flex items-center gap-3">
                                @if($user->profile_picture)
                                    <img src="{{ \Illuminate\Support\Str::startsWith($user->profile_picture, ['http://','https://']) ? $user->profile_picture : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($user->profile_picture) }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border">
                                @else
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-800">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600" data-label="Phone">{{ $user->phone_number ?? '-' }}</td>
                        <td class="px-6 py-4" data-label="Role">
                            <x-status-badge :status="$user->role" />
                        </td>
                        <td class="px-6 py-4" data-label="Status">
                            @if($user->is_active)
                                <span class="text-green-600 text-xs font-medium"><i class="fas fa-circle text-green-500 mr-1" style="font-size:6px"></i>Active</span>
                            @else
                                <span class="text-red-600 text-xs font-medium"><i class="fas fa-circle text-red-500 mr-1" style="font-size:6px"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs" data-label="Joined">{{ $user->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4 text-right" data-label="Actions">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800 mr-2" title="View"><i class="fas fa-eye"></i></a>
                            @if(auth()->user()->hasAdminPermission('users.manage'))
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-yellow-600 hover:text-yellow-800 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                @unless($user->is(auth()->user()))
                                    <button type="button" class="text-red-600 hover:text-red-800" title="Delete"
                                        x-data
                                        @click="$dispatch('confirm-modal', {
                                            title: 'Delete User',
                                            message: 'Delete {{ addslashes($user->name) }}? This user will no longer be able to access their account.',
                                            confirmText: 'Delete',
                                            onConfirm: () => {
                                                const form = document.createElement('form');
                                                form.method = 'POST';
                                                form.action = '{{ route('admin.users.destroy', $user) }}';
                                                const i1=document.createElement('input');i1.type='hidden';i1.name='_token';i1.value='{{ csrf_token() }}';form.appendChild(i1);const i2=document.createElement('input');i2.type='hidden';i2.name='_method';i2.value='DELETE';form.appendChild(i2);
                                                document.body.appendChild(form);
                                                form.submit();
                                            }
                                        })">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endunless
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state icon="fa-users" title="No users found" description="Try adjusting your search or filter criteria." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t flex items-center justify-between">
        <x-pagination-info :paginator="$users" />
        {{ $users->links() }}
    </div>
</div>
</form>
@endsection
