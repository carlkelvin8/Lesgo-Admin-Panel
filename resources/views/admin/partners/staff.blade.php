@extends('admin.layouts.app')
@section('title', $partner->name.' Staff - LesGo Admin')
@section('header', $partner->name.' Staff')

@section('actions')
<a href="{{ route('admin.partners.menu.index', $partner) }}" class="border px-4 py-2 rounded-lg text-sm">Manage Menu</a>
<a href="{{ route('admin.partners.show', $partner) }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Back to Partner</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form method="POST" action="{{ route('admin.partners.staff.store', $partner) }}" class="bg-white rounded-xl shadow-sm p-6 h-fit space-y-4">@csrf<h3 class="font-semibold text-lg">Add Staff Member</h3><div><label class="block text-sm mb-1">User</label><select name="user_id" required class="w-full border rounded-lg px-3 py-2"><option value="">Select user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }} ({{ $user->role }})</option>@endforeach</select></div><div><label class="block text-sm mb-1">Staff role</label><select name="role" class="w-full border rounded-lg px-3 py-2"><option value="cashier">Cashier</option><option value="cook">Cook</option><option value="admin">Admin</option></select></div><div><p class="text-sm mb-2">Permissions</p><div class="grid grid-cols-2 gap-2 text-sm">@foreach(['orders','menu','reports','staff'] as $permission)<label><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ ucfirst($permission) }}</label>@endforeach</div></div><button class="bg-blue-600 text-white px-4 py-2 rounded-lg" @disabled($users->isEmpty())>Add Staff</button>@if($users->isEmpty())<p class="text-xs text-gray-500">All active users are already assigned.</p>@endif</form>
    <div class="lg:col-span-2 space-y-4">
        @forelse($partner->staff as $staff)
        <div class="bg-white rounded-xl shadow-sm p-5"><div class="flex justify-between gap-3 mb-4"><div><p class="font-semibold">{{ $staff->user->name ?? 'Deleted user' }}</p><p class="text-sm text-gray-500">{{ $staff->user->email ?? '—' }}</p></div><span class="px-2 py-1 rounded-full h-fit text-xs {{ $staff->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $staff->is_active ? 'Active' : 'Inactive' }}</span></div><form method="POST" action="{{ route('admin.partners.staff.update', [$partner, $staff]) }}" class="space-y-3">@csrf @method('PUT')<div class="grid grid-cols-2 gap-3"><select name="role" class="border rounded-lg px-3 py-2">@foreach(['cashier','cook','admin'] as $role)<option value="{{ $role }}" @selected($staff->role === $role)>{{ ucfirst($role) }}</option>@endforeach</select><label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($staff->is_active)> Active access</label></div><div class="flex flex-wrap gap-4 text-sm">@foreach(['orders','menu','reports','staff'] as $permission)<label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $staff->permissions ?? [], true))> {{ ucfirst($permission) }}</label>@endforeach</div><button class="text-blue-600 text-sm">Save access</button></form><form method="POST" action="{{ route('admin.partners.staff.destroy', [$partner, $staff]) }}" class="mt-2" onsubmit="return confirm('Remove this staff member?')">@csrf @method('DELETE')<button class="text-red-600 text-sm">Remove staff member</button></form></div>
        @empty<div class="bg-white rounded-xl p-10 text-center text-gray-400">No staff assigned.</div>@endforelse
    </div>
</div>
@endsection
