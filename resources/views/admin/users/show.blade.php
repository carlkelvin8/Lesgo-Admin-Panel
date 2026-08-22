@extends('admin.layouts.app')
@section('title', 'User Details - LesGo Admin')
@section('header', 'User Details')

@section('actions')
@if(auth()->user()->hasAdminPermission('users.manage'))
<a href="{{ route('admin.users.edit', $user) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
@unless($user->is(auth()->user()))
<form action="{{ route('admin.users.toggle', $user) }}" method="POST" class="inline">
    @csrf
    <button type="submit" class="bg-{{ $user->is_active ? 'red' : 'green' }}-500 hover:bg-{{ $user->is_active ? 'red' : 'green' }}-600 text-white px-4 py-2 rounded-lg text-sm">
        <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }} mr-1"></i> {{ $user->is_active ? 'Deactivate' : 'Activate' }}
    </button>
</form>
<form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This user will no longer be able to access their account.')">
    @csrf
    @method('DELETE')
    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
        <i class="fas fa-trash mr-1"></i> Delete
    </button>
</form>
@endunless
@endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-center">
            <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-2xl mx-auto mb-4">
                {{ substr($user->name, 0, 1) }}
            </div>
            <h3 class="text-xl font-bold text-gray-800">{{ $user->name }}</h3>
            <p class="text-gray-500">{{ $user->email }}</p>
            <x-status-badge :status="$user->role" />
        </div>
        <div class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Phone</span>
                <span class="text-gray-800">{{ $user->phone_number ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Status</span>
                <span class="text-{{ $user->is_active ? 'green' : 'red' }}-600 font-medium">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Joined</span>
                <span class="text-gray-800">{{ $user->created_at->format('M d, Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Last Updated</span>
                <span class="text-gray-800">{{ $user->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    <!-- Activity -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Driver Profile -->
        @if($user->driverProfile)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-motorcycle mr-2 text-green-600"></i>Driver Profile</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><p class="text-gray-500">Status</p><p class="font-medium">{{ ucfirst($user->driverProfile->status) }}</p></div>
                <div><p class="text-gray-500">Rating</p><p class="font-medium">{{ $user->driverProfile->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></p></div>
                <div><p class="text-gray-500">Total Trips</p><p class="font-medium">{{ $user->driverProfile->total_trips }}</p></div>
                <div><p class="text-gray-500">License</p><p class="font-medium">{{ $user->driverProfile->license_number ?? '-' }}</p></div>
            </div>
        </div>
        @endif

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-shopping-cart mr-2 text-blue-600"></i>Recent Orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">ID</th>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">Fare</th>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($user->orders as $order)
                            <tr>
                                <td class="px-6 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">#{{ $order->id }}</a></td>
                                <td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100">{{ ucfirst($order->status) }}</span></td>
                                <td class="px-6 py-3">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td>
                                <td class="px-6 py-3 text-gray-500 text-xs">{{ $order->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
