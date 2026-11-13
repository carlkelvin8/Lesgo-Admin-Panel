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
    <x-filter-input name="role" label="Role" type="select" :options="['' => 'All roles'] + array_combine(\App\Models\User::ROLES, array_map(fn ($role) => \Illuminate\Support\Str::headline($role), \App\Models\User::ROLES))" />
    <x-filter-input name="scope" label="Accounts" type="select" :options="['current' => 'Current accounts', 'deleted' => 'Deleted accounts', 'all' => 'All accounts']" />
    <x-filter-input name="status" label="Status" type="select" :options="['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
</x-filter-panel>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ loading: false }" x-init="loading = false">
    <div class="overflow-x-auto">
        <table class="w-full text-sm responsive-table">
            <thead class="bg-gray-50 border-b">
                <tr>
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
                        <td class="px-6 py-4" data-label="User">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
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
                            @if($user->trashed())
                                <span class="text-gray-500 text-xs font-medium">Deleted account</span>
                            @elseif($user->is_active)
                                <span class="text-green-600 text-xs font-medium"><i class="fas fa-circle text-green-500 mr-1" style="font-size:6px"></i>Active</span>
                            @else
                                <span class="text-red-600 text-xs font-medium"><i class="fas fa-circle text-red-500 mr-1" style="font-size:6px"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs" data-label="Joined">{{ $user->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4 text-right" data-label="Actions">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800 mr-2" title="View"><i class="fas fa-eye"></i></a>
                            @if(auth()->user()->hasAdminPermission('users.manage') && ! $user->trashed())
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-yellow-600 hover:text-yellow-800 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                @unless($user->is(auth()->user()))
                                    <x-confirm-action :action="route('admin.users.destroy', $user)" method="DELETE" title="Delete User"
                                        :message="'Delete '.$user->name.'? This user will no longer be able to access their account.'"
                                        confirm-text="Delete" class="text-red-600 hover:text-red-800" aria-label="Delete user">
                                        <i class="fas fa-trash"></i>
                                    </x-confirm-action>
                                @endunless
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
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
@endsection
