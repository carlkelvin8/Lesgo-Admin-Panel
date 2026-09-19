@extends('admin.layouts.app')
@section('title', 'Analytics - LesGo Admin')
@section('header', 'Analytics')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- ── KPI Cards ────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
        <p class="text-xs text-gray-500 mb-1">Revenue (30d)</p>
        <p class="text-2xl font-bold text-gray-800">₱{{ number_format($stats['total_revenue'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">from completed orders</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
        <p class="text-xs text-gray-500 mb-1">Orders (30d)</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_orders']) }}</p>
        <p class="text-xs text-gray-400 mt-1">all statuses</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
        <p class="text-xs text-gray-500 mb-1">Avg Daily Revenue</p>
        <p class="text-2xl font-bold text-gray-800">₱{{ number_format($stats['avg_daily_revenue'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">over active days</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
        <p class="text-xs text-gray-500 mb-1">New Users (30d)</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_new_users']) }}</p>
        <p class="text-xs text-gray-400 mt-1">registrations</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-pink-500">
        <p class="text-xs text-gray-500 mb-1">Transactions (30d)</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_transactions']) }}</p>
        <p class="text-xs text-gray-400 mt-1">completed orders</p>
    </div>
</div>

{{-- ── Row 1: Revenue trend + Order status ─────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Revenue &amp; Orders — 30 Days</h3>
            <span class="text-xs text-gray-400">daily completed orders</span>
        </div>
        @if(count($trendLabels))
        <div class="h-64"><canvas id="revenueTrendChart"></canvas></div>
        @else
        <div class="h-64 flex items-center justify-center text-gray-400 text-sm">No trend data yet.</div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Order Status (30d)</h3>
        @if($orderStatusDist->count())
        <div class="h-48"><canvas id="orderStatusChart"></canvas></div>
        <div class="mt-3 space-y-1" id="statusLegend"></div>
        @else
        <div class="h-48 flex items-center justify-center text-gray-400 text-sm">No data.</div>
        @endif
    </div>
</div>

{{-- ── Row 2: New users + Hourly heatmap ───────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">New User Registrations</h3>
            <span class="text-xs text-gray-400">30 days</span>
        </div>
        @if(count($dailyUserLabels))
        <div class="h-48"><canvas id="newUsersChart"></canvas></div>
        @else
        <div class="h-48 flex items-center justify-center text-gray-400 text-sm">No signups yet.</div>
        @endif
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Peak Order Hours</h3>
            <span class="text-xs text-gray-400">last 7 days</span>
        </div>
        <div class="h-48"><canvas id="hourlyChart"></canvas></div>
    </div>
</div>

{{-- ── Row 3: Services + Payment methods + Revenue by type ────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Orders by Service (30d)</h3>
        @if($ordersByService->count())
        <div class="h-52"><canvas id="serviceChart"></canvas></div>
        @else
        <div class="h-52 flex items-center justify-center text-gray-400 text-sm">No service data.</div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Payment Methods (30d)</h3>
        @if($paymentMethods->count())
        <div class="h-52"><canvas id="paymentMethodChart"></canvas></div>
        @else
        <div class="h-52 flex items-center justify-center text-gray-400 text-sm">No payment data.</div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Revenue by Type (30d)</h3>
        @forelse($revenueByType as $type)
        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $type->revenue_type) }}</span>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-800">₱{{ number_format($type->total_amount, 2) }}</p>
                <p class="text-xs text-gray-400">{{ number_format($type->total_transactions) }} txns</p>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-4">No revenue data.</p>
        @endforelse
    </div>
</div>

{{-- ── Row 4: Today's metrics + Recent reports ─────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-bolt mr-2 text-yellow-400"></i>Today's Metrics</h3>
        @forelse($todayMetrics as $type => $metrics)
        <div class="mb-4 last:mb-0">
            <h4 class="text-xs uppercase tracking-wider text-gray-400 mb-2">{{ str_replace('_', ' ', $type) }}</h4>
            @foreach($metrics as $metric)
            <div class="flex items-center justify-between py-2 pl-4 border-l-2 border-purple-200">
                <span class="text-sm text-gray-600">{{ str_replace('_', ' ', $metric->metric_key) }}</span>
                <span class="text-sm font-semibold text-gray-800">{{ number_format($metric->metric_value, 2) }}</span>
            </div>
            @endforeach
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-4">No metrics recorded today.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-calendar-check mr-2 text-gray-400"></i>Recent Daily Reports</h3>
            <a href="{{ route('admin.reports.index') }}" class="text-xs text-blue-600 hover:underline">View all</a>
        </div>
        @forelse($recentReports as $report)
        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <div>
                <a href="{{ route('admin.reports.daily', $report->report_date->format('Y-m-d')) }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                    {{ $report->report_date->format('M d, Y') }}
                </a>
                <p class="text-xs text-gray-400">{{ number_format($report->completed_orders) }}/{{ number_format($report->total_orders) }} completed</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-green-600">₱{{ number_format($report->total_revenue, 2) }}</p>
                <p class="text-xs text-gray-400">{{ number_format($report->total_distance_km, 1) }} km</p>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-4">No daily reports generated yet.</p>
        @endforelse
    </div>
</div>

{{-- ── Event stats ──────────────────────────────────────────────────────────── --}}
@if($eventStats->count())
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-mouse-pointer mr-2 text-gray-400"></i>Top Events (7d)</h3>
    @php $maxEvent = $eventStats->max('count'); @endphp
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Event</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Count</th>
                    <th class="px-6 py-3 w-40"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($eventStats as $event)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-700 capitalize">{{ str_replace('_', ' ', $event->event_type) }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($event->count) }}</td>
                    <td class="px-6 py-3">
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $maxEvent > 0 ? round(($event->count / $maxEvent) * 100) : 0 }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
Chart.defaults.font.family = "'Inter','ui-sans-serif',system-ui,sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#6b7280';
Chart.defaults.plugins.legend.display = false;

const PURPLE = '#7c3aed', BLUE = '#3b82f6', GREEN = '#22c55e',
      ORANGE = '#f97316', PINK = '#ec4899', TEAL = '#14b8a6',
      YELLOW = '#eab308';
const PALETTE = [PURPLE,BLUE,GREEN,ORANGE,PINK,TEAL,YELLOW,'#6366f1','#f43f5e','#84cc16'];
const TIP = { backgroundColor:'rgba(17,24,39,0.92)', titleColor:'#f9fafb', bodyColor:'#d1d5db', padding:10, cornerRadius:8 };

// ── 1. Revenue + Orders trend ─────────────────────────────────────────────
@if(count($trendLabels))
new Chart(document.getElementById('revenueTrendChart'), {
    data: {
        labels: @json($trendLabels),
        datasets: [
            {
                type:'line', label:'Revenue (₱)',
                data: @json($trendRevenue),
                borderColor:PURPLE, backgroundColor:'rgba(124,58,237,0.08)',
                borderWidth:2.5, pointRadius:3, pointHoverRadius:5,
                fill:true, tension:0.4, yAxisID:'y',
            },
            {
                type:'bar', label:'Orders',
                data: @json($trendOrders),
                backgroundColor:'rgba(59,130,246,0.18)', borderColor:BLUE,
                borderWidth:1, borderRadius:4, yAxisID:'y2',
            }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        plugins:{
            legend:{ display:true, position:'top', labels:{ boxWidth:12, padding:16 } },
            tooltip:{ ...TIP, callbacks:{
                label: ctx => ctx.datasetIndex===0
                    ? ' \u20b1'+ctx.parsed.y.toLocaleString('en-PH',{minimumFractionDigits:2})
                    : ' '+ctx.parsed.y+' orders'
            }}
        },
        scales:{
            x:{ grid:{display:false}, ticks:{maxTicksLimit:10} },
            y:{ position:'left', grid:{color:'rgba(0,0,0,0.05)'}, ticks:{callback:v=>'₱'+v.toLocaleString()} },
            y2:{ position:'right', grid:{drawOnChartArea:false}, ticks:{stepSize:1} }
        }
    }
});
@endif

// ── 2. Order status donut ─────────────────────────────────────────────────
@if($orderStatusDist->count())
(function(){
    const statusColors = {
        completed:GREEN, cancelled:'#ef4444', pending:YELLOW,
        searching_driver:BLUE, accepted:TEAL, picked_up:ORANGE,
        in_progress:PURPLE, driver_arrived_at_pickup:PINK,
    };
    const labels = @json($orderStatusDist->pluck('status'));
    const data   = @json($orderStatusDist->pluck('total'));
    const colors = labels.map(l => statusColors[l]||'#94a3b8');

    new Chart(document.getElementById('orderStatusChart'),{
        type:'doughnut',
        data:{ labels, datasets:[{ data, backgroundColor:colors, borderWidth:2, borderColor:'#fff', hoverOffset:6 }] },
        options:{
            responsive:true, maintainAspectRatio:false, cutout:'68%',
            plugins:{ legend:{display:false}, tooltip:TIP }
        }
    });

    const legend = document.getElementById('statusLegend');
    const total  = data.reduce((a,b)=>a+b,0);
    labels.forEach((l,i)=>{
        const pct = total>0?((data[i]/total)*100).toFixed(1):0;
        legend.innerHTML += `<div class="flex items-center justify-between text-xs text-gray-600">
            <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${colors[i]}"></span>
                <span class="capitalize">${l.replace(/_/g,' ')}</span>
            </span>
            <span class="font-medium">${parseInt(data[i]).toLocaleString()} <span class="text-gray-400">(${pct}%)</span></span>
        </div>`;
    });
})();
@endif

// ── 3. New users bar ──────────────────────────────────────────────────────
@if(count($dailyUserLabels))
new Chart(document.getElementById('newUsersChart'),{
    type:'bar',
    data:{
        labels: @json($dailyUserLabels),
        datasets:[{ data: @json($dailyUserValues),
            backgroundColor:'rgba(249,115,22,0.75)', borderColor:ORANGE,
            borderWidth:1, borderRadius:4 }]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ tooltip:{ ...TIP, callbacks:{ label:ctx=>' '+ctx.parsed.y+' new users' }} },
        scales:{ x:{grid:{display:false},ticks:{maxTicksLimit:8}}, y:{grid:{color:'rgba(0,0,0,0.05)'},ticks:{stepSize:1}} }
    }
});
@endif

// ── 4. Hourly heatmap ─────────────────────────────────────────────────────
(function(){
    const hours  = @json($hourlyLabels);
    const data   = @json($hourlyValues);
    const maxVal = Math.max(...data,1);
    const colors = data.map(v=>`rgba(124,58,237,${(0.15+((v/maxVal)*0.75)).toFixed(2)})`);

    new Chart(document.getElementById('hourlyChart'),{
        type:'bar',
        data:{ labels:hours, datasets:[{ data, backgroundColor:colors, borderWidth:1, borderRadius:3 }] },
        options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{ tooltip:{ ...TIP, callbacks:{ label:ctx=>' '+ctx.parsed.y+' orders' }} },
            scales:{ x:{grid:{display:false},ticks:{maxRotation:45}}, y:{grid:{color:'rgba(0,0,0,0.05)'},ticks:{stepSize:1}} }
        }
    });
})();

// ── 5. Orders by service (horizontal bar) ────────────────────────────────
@if($ordersByService->count())
(function(){
    const labels = @json($ordersByService->pluck('service_name'));
    const data   = @json($ordersByService->pluck('total'));
    const colors = labels.map((_,i)=>PALETTE[i%PALETTE.length]);

    new Chart(document.getElementById('serviceChart'),{
        type:'bar',
        data:{ labels, datasets:[{ data,
            backgroundColor:colors.map(c=>c+'99'), borderColor:colors,
            borderWidth:1.5, borderRadius:4 }]
        },
        options:{
            indexAxis:'y', responsive:true, maintainAspectRatio:false,
            plugins:{ tooltip:{ ...TIP, callbacks:{ label:ctx=>' '+ctx.parsed.x+' orders' }} },
            scales:{ x:{grid:{color:'rgba(0,0,0,0.05)'},ticks:{stepSize:1}}, y:{grid:{display:false}} }
        }
    });
})();
@endif

// ── 6. Payment methods pie ────────────────────────────────────────────────
@if($paymentMethods->count())
(function(){
    const labels = @json($paymentMethods->pluck('payment_method'));
    const data   = @json($paymentMethods->pluck('total'));
    const colors = labels.map((_,i)=>PALETTE[i%PALETTE.length]);

    new Chart(document.getElementById('paymentMethodChart'),{
        type:'pie',
        data:{ labels, datasets:[{ data, backgroundColor:colors, borderColor:'#fff', borderWidth:2, hoverOffset:6 }] },
        options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{
                legend:{ display:true, position:'bottom', labels:{ boxWidth:12, padding:10, font:{size:11} } },
                tooltip:{ ...TIP, callbacks:{ label:ctx=>{
                    const total=ctx.dataset.data.reduce((a,b)=>a+b,0);
                    const pct=total>0?((ctx.parsed/total)*100).toFixed(1):0;
                    return ` ${ctx.parsed} orders (${pct}%)`;
                }}}
            }
        }
    });
})();
@endif
</script>
@endsection
