@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card label="Total Users" :value="$stats['total_users'] ?? 0" icon="users" color="blue" />
        <x-stat-card label="Total Orders" :value="$stats['total_orders'] ?? 0" icon="shopping-cart" color="green" />
        <x-stat-card label="Total Partners" :value="$stats['total_partners'] ?? 0" icon="building" color="purple" />
        <x-stat-card label="Total Drivers" :value="$stats['total_drivers'] ?? 0" icon="truck" color="orange" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card label="Revenue" :value="$stats['total_revenue'] ?? 0" icon="dollar-sign" color="green" format="currency" />
        <x-stat-card label="Open Tickets" :value="$stats['open_tickets'] ?? 0" icon="ticket" color="yellow" />
        <x-stat-card label="Pending Orders" :value="$stats['pending_orders'] ?? 0" icon="clock" color="red" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-stat-card label="Completed Orders" :value="$stats['completed_orders'] ?? 0" icon="check-circle" color="green" />
        <x-stat-card label="Active Users" :value="$stats['active_users'] ?? 0" icon="user-check" color="blue" />
        <x-stat-card label="Active Partners" :value="$stats['active_partners'] ?? 0" icon="building" color="purple" />
        <x-stat-card label="Pending Reviews" :value="$stats['pending_reviews'] ?? 0" icon="star" color="yellow" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-card href="{{ route('admin.document-verifications.index') }}" border hover class="cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <i class="fas fa-shield-halved w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Pending Verifications</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['pending_verifications'] ?? 0 }} awaiting review</p>
                </div>
            </div>
        </x-card>

        <x-card href="{{ route('admin.ratings.index') }}" border hover class="cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-star w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Reviews Needing Moderation</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['pending_reviews'] ?? 0 }} to moderate</p>
                </div>
            </div>
        </x-card>

        <x-card href="{{ route('admin.security-events.index') }}" border hover class="cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle w-6 h-6 text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Open Security Events</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['open_security_events'] ?? 0 }} unresolved</p>
                </div>
            </div>
        </x-card>
    </div>

    <div x-data="dashboardCharts()">
        <div class="flex items-center justify-between mb-4">
            <div></div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Period:</span>
                @foreach([7 => '7D', 14 => '14D', 30 => '30D', 60 => '60D', 90 => '90D'] as $d => $label)
                    <button @click="updateDays({{ $d }})" class="px-3 py-1 text-xs rounded-lg {{ ($days ?? 7) == $d ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} transition">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue Overview</h3>
                </x-slot>
                <div class="h-64">
                    <canvas x-ref="revenueChart" x-init="initRevenue()"></canvas>
                </div>
            </x-card>

            <x-card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Order Status Distribution</h3>
                </x-slot>
                <div class="h-64 flex items-center justify-center">
                    <canvas x-ref="orderStatusChart" x-init="initOrderStatus()"></canvas>
                </div>
            </x-card>
        </div>

        <div class="mt-6">
            <x-card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Growth</h3>
                </x-slot>
                <div class="h-64">
                    <canvas x-ref="userGrowthChart" x-init="initUserGrowth()"></canvas>
                </div>
            </x-card>
        </div>
    </div>

    <x-card>
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Partners</h3>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="responsive-table w-full">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="px-4 py-3" data-label="Partner">Partner</th>
                        <th class="px-4 py-3" data-label="Orders">Orders</th>
                        <th class="px-4 py-3" data-label="Revenue">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topPartners as $entry)
                        @php
                            $partner = data_get($entry, 'partner');
                            if (is_string($partner)) $partner = null;
                            $partnerName = data_get($partner, 'name', 'N/A');
                            $partnerEmail = data_get($partner, 'email', data_get($partner, 'user.email', ''));
                            $partnerAvatar = data_get($partner, 'avatar', data_get($partner, 'logo_url'));
                            $orderCount = data_get($entry, 'order_count', 0);
                            $revenue = data_get($entry, 'revenue', 0);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4" data-label="Partner">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        @if($partnerAvatar)
                                            <img src="{{ $partnerAvatar }}" alt="{{ $partnerName }}" class="w-8 h-8 rounded-full object-cover">
                                        @else
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ substr($partnerName, 0, 2) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $partnerName }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $partnerEmail }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4" data-label="Orders">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $orderCount }}</span>
                            </td>
                            <td class="px-4 py-4" data-label="Revenue">
                                <span class="font-medium text-gray-900 dark:text-white">${{ number_format((float) $revenue, 2) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-empty-state icon="building" title="No partners found" description="No partner data available yet." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h3>
                    <a href="{{ route('admin.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">View All</a>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-4 py-3" data-label="Order ID">Order ID</th>
                            <th class="px-4 py-3" data-label="Customer">Customer</th>
                            <th class="px-4 py-3" data-label="Total">Total</th>
                            <th class="px-4 py-3" data-label="Status">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recent_orders as $order)
                            @if(is_string($order) || ! is_object($order)) @continue @endif
                            @php $orderId = data_get($order, 'id'); $orderCustomer = data_get($order, 'customer'); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4" data-label="Order ID">
                                    <a href="{{ $orderId ? route('admin.orders.show', $order) : '#' }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">#{{ $orderId }}</a>
                                </td>
                                <td class="px-4 py-4" data-label="Customer">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ data_get($orderCustomer, 'name', 'N/A') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ data_get($orderCustomer, 'email', '') }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-4" data-label="Total">
                                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format((float) data_get($order, 'total', 0), 2) }}</span>
                                </td>
                                <td class="px-4 py-4" data-label="Status">
                                    <x-status-badge :status="data_get($order, 'status', 'unknown')" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty-state icon="shopping-cart" title="No recent orders" description="No orders have been placed yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Users</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">View All</a>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-4 py-3" data-label="User">User</th>
                            <th class="px-4 py-3" data-label="Role">Role</th>
                            <th class="px-4 py-3" data-label="Joined">Joined</th>
                            <th class="px-4 py-3" data-label="Status">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recent_users as $user)
                            @if(is_string($user) || ! is_object($user)) @continue @endif
                            @php $userName = data_get($user, 'name', 'N/A'); $userEmail = data_get($user, 'email', ''); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4" data-label="User">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            @if(data_get($user, 'avatar'))
                                                <img src="{{ data_get($user, 'avatar') }}" alt="{{ $userName }}" class="w-8 h-8 rounded-full object-cover">
                                            @else
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ substr($userName, 0, 2) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $userName }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $userEmail }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4" data-label="Role">
                                    <x-status-badge :status="data_get($user, 'role', 'customer')" />
                                </td>
                                <td class="px-4 py-4" data-label="Joined">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ data_get($user, 'created_at') ? \Illuminate\Support\Carbon::parse(data_get($user, 'created_at'))->diffForHumans() : '' }}</span>
                                </td>
                                <td class="px-4 py-4" data-label="Status">
                                    <x-status-badge :status="data_get($user, 'status', 'active')" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty-state icon="users" title="No recent users" description="No users have registered yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
window.dashboardData = {
    dailyRevenue: @json($dailyRevenue),
    orderStatusDistribution: @json($orderStatusDistribution),
    dailyUsers: @json($dailyUsers)
};
</script>
@vite('resources/js/dashboard-charts.js')
@endsection
