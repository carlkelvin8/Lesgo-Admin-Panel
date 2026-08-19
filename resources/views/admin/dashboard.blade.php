@extends('admin.layouts.app')

@section('title', 'Dashboard - LesGo Admin')
@section('header', 'Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_users']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_orders']) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Partners</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_partners']) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-store text-purple-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Drivers</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_drivers']) }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-motorcycle text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Revenue & Tickets -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-500">Total Revenue</p>
        <p class="text-3xl font-bold text-green-600">₱{{ number_format($stats['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-500">Open Tickets</p>
        <p class="text-3xl font-bold text-yellow-600">{{ $stats['open_tickets'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-500">Pending Orders</p>
        <p class="text-3xl font-bold text-red-600">{{ $stats['pending_orders'] }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="{{ route('admin.document-verifications.index', ['status' => 'pending']) }}" class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 hover:shadow-md"><p class="text-sm text-gray-500">Pending Verifications</p><p class="text-2xl font-bold text-blue-600">{{ number_format($stats['pending_verifications']) }}</p></a>
    <a href="{{ route('admin.ratings.index', ['status' => 'flagged']) }}" class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 hover:shadow-md"><p class="text-sm text-gray-500">Reviews Needing Moderation</p><p class="text-2xl font-bold text-orange-600">{{ number_format($stats['pending_reviews']) }}</p></a>
    <a href="{{ route('admin.security-events.index', ['status' => 'open']) }}" class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500 hover:shadow-md"><p class="text-sm text-gray-500">Open Security Events</p><p class="text-2xl font-bold text-red-600">{{ number_format($stats['open_security_events']) }}</p></a>
</div>

<!-- Recent Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Orders -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Recent Orders</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">ID</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Customer</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Fare</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recent_orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">#{{ $order->id }}</a>
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ $order->customer->name ?? 'N/A' }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'accepted' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-700">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Recent Users</h3>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Name</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Email</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Role</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recent_users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:underline">{{ $user->name }}</a>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @if($user->is_active)
                                    <span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span> Active
                                @else
                                    <span class="w-2 h-2 bg-red-500 rounded-full inline-block"></span> Inactive
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
