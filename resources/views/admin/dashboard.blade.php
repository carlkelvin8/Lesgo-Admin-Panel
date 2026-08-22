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
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4" data-label="Partner">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        @if($entry->partner && $entry->partner->avatar)
                                            <img src="{{ $entry->partner->avatar }}" alt="{{ $entry->partner->name ?? '' }}" class="w-8 h-8 rounded-full object-cover">
                                        @else
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ substr($entry->partner->name ?? 'P', 0, 2) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $entry->partner->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entry->partner->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4" data-label="Orders">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $entry->order_count ?? 0 }}</span>
                            </td>
                            <td class="px-4 py-4" data-label="Revenue">
                                <span class="font-medium text-gray-900 dark:text-white">${{ number_format($entry->revenue ?? 0, 2) }}</span>
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
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4" data-label="Order ID">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">#{{ $order->id }}</a>
                                </td>
                                <td class="px-4 py-4" data-label="Customer">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order->customer->email ?? '' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-4" data-label="Total">
                                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($order->total ?? 0, 2) }}</span>
                                </td>
                                <td class="px-4 py-4" data-label="Status">
                                    <x-status-badge :status="$order->status" />
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
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4" data-label="User">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            @if($user->avatar)
                                                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover">
                                            @else
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ substr($user->name, 0, 2) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4" data-label="Role">
                                    <x-status-badge :status="$user->role ?? 'customer'" />
                                </td>
                                <td class="px-4 py-4" data-label="Joined">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="px-4 py-4" data-label="Status">
                                    <x-status-badge :status="$user->status ?? 'active'" />
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function dashboardCharts() {
    return {
        revenueChart: null,
        orderStatusChart: null,
        userGrowthChart: null,

        initRevenue() {
            const ctx = this.$refs.revenueChart.getContext('2d');
            const data = @json($dailyRevenue);
            this.revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.date),
                    datasets: [{
                        label: 'Revenue',
                        data: data.map(item => item.total),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(ctx) {
                                    return '$' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6b7280', maxTicksLimit: 7 }
                        },
                        y: {
                            grid: { color: 'rgba(107,114,128,0.1)' },
                            ticks: {
                                color: '#6b7280',
                                callback: function(v) { return '$' + v.toLocaleString(); }
                            },
                            beginAtZero: true
                        }
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false }
                }
            });
        },

        initOrderStatus() {
            const ctx = this.$refs.orderStatusChart.getContext('2d');
            const data = @json($orderStatusDistribution);
            const statusColors = {
                'pending': '#f59e0b', 'processing': '#3b82f6', 'completed': '#10b981',
                'cancelled': '#ef4444', 'refunded': '#8b5cf6', 'delivered': '#06b6d4', 'shipped': '#ec4899'
            };
            const defaults = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#6366f1'];
            const labels = data.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
            const values = data.map(item => item.total);
            const colors = data.map((item, i) => statusColors[item.status.toLowerCase()] || defaults[i % defaults.length]);

            this.orderStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: values, backgroundColor: colors, borderColor: 'transparent', borderWidth: 0, hoverOffset: 8 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', color: '#6b7280', font: { size: 12 } }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#374151',
                            borderWidth: 1,
                            callbacks: {
                                label: function(ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                    return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        },

        initUserGrowth() {
            const ctx = this.$refs.userGrowthChart.getContext('2d');
            const data = @json($dailyUsers);
            this.userGrowthChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.date),
                    datasets: [{
                        label: 'New Users',
                        data: data.map(item => item.total),
                        backgroundColor: 'rgba(99,102,241,0.8)',
                        borderColor: '#6366f1',
                        borderWidth: 0,
                        borderRadius: 4,
                        barThickness: 'flex',
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#6366f1',
                            borderWidth: 1,
                            callbacks: {
                                label: function(ctx) { return ctx.parsed.y + ' users'; }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6b7280', maxTicksLimit: 7 }
                        },
                        y: {
                            grid: { color: 'rgba(107,114,128,0.1)' },
                            ticks: { color: '#6b7280', stepSize: 1 },
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    };
}
</script>
@endsection
