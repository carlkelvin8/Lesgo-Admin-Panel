let chartLoad = null;

function ensureChart() {
    if (window.Chart) return Promise.resolve(window.Chart);
    if (!chartLoad) {
        chartLoad = import('chart.js/auto').then((mod) => {
            window.Chart = mod.default ?? mod.Chart ?? mod;
            return window.Chart;
        });
    }
    return chartLoad;
}

function asChartArray(value) {
    if (Array.isArray(value)) return value;
    if (value !== null && typeof value === 'object') return Object.values(value);
    return [];
}

function dashboardCharts() {
    return {
        revenueChart: null,
        orderStatusChart: null,
        userGrowthChart: null,
        days: 7,

        async initRevenue() {
            const Chart = await ensureChart();
            const ctx = this.$refs.revenueChart.getContext('2d');
            const data = asChartArray(window.dashboardData?.dailyRevenue);
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

        async initOrderStatus() {
            const Chart = await ensureChart();
            const ctx = this.$refs.orderStatusChart.getContext('2d');
            const data = asChartArray(window.dashboardData?.orderStatusDistribution);
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

        async initUserGrowth() {
            const Chart = await ensureChart();
            const ctx = this.$refs.userGrowthChart.getContext('2d');
            const data = asChartArray(window.dashboardData?.dailyUsers);

            if (data.length === 0) {
                // Show a friendly empty state inside the canvas area
                const canvas = this.$refs.userGrowthChart;
                const parent = canvas.parentElement;
                canvas.style.display = 'none';
                const msg = document.createElement('div');
                msg.className = 'h-full flex flex-col items-center justify-center text-gray-400 gap-2';
                msg.innerHTML = '<svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg><p class="text-sm font-medium">No user growth yet</p><p class="text-xs opacity-70">New registrations will appear here</p>';
                parent.appendChild(msg);
                return;
            }

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
        },

        updateDays(newDays) {
            this.days = newDays;
            const params = new URLSearchParams(window.location.search);
            params.set('days', newDays);
            window.location.href = window.location.pathname + '?' + params.toString();
        }
    };
}

window.dashboardCharts = dashboardCharts;
document.addEventListener('alpine:init', () => {
    window.Alpine.data('dashboardCharts', dashboardCharts);
});