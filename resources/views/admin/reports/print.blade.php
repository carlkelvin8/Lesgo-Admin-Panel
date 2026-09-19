<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - {{ $report->report_date->format('Y-m-d') }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 13px;
            color: #1e1b4b;
            line-height: 1.5;
            background: #ffffff;
            padding: 30px;
        }
        .report-container { max-width: 800px; margin: 0 auto; }
        .report-header {
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 16px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .report-header h1 { font-size: 22px; color: #1e1b4b; }
        .report-header p { color: #6b7280; font-size: 13px; }
        .section { margin-bottom: 24px; }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e9d5ff;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: 600; color: #374151; font-size: 11px; text-transform: uppercase; }
        td { font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .metric-card .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .metric-card .value {
            font-size: 20px;
            font-weight: 700;
            color: #7c3aed;
        }
        .report-footer {
            margin-top: 32px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
        }
        .no-print { display: none !important; }
        .text-right { text-align: right; }
        .text-bold { font-weight: 700; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }
        .text-gray { color: #6b7280; }
        @media print {
            .metrics-grid { grid-template-columns: repeat(4, 1fr); }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <div>
                <h1>LesGo Daily Report</h1>
                <p>Report Date: {{ $report->report_date->format('F j, Y') }}</p>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">Total Revenue</div>
                <div class="value">₱{{ number_format($report->total_revenue, 2) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Orders</div>
                <div class="value">{{ number_format($report->total_orders) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">New Users</div>
                <div class="value">{{ number_format($report->new_users) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">New Drivers</div>
                <div class="value">{{ number_format($report->new_drivers) }}</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Order Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Share</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Completed Orders</td>
                        <td class="text-right text-bold">{{ number_format($report->completed_orders) }}</td>
                        <td class="text-right text-green">{{ $completionRate }}%</td>
                    </tr>
                    <tr>
                        <td>Cancelled Orders</td>
                        <td class="text-right text-bold">{{ number_format($report->cancelled_orders) }}</td>
                        <td class="text-right text-red">{{ $cancelRate }}%</td>
                    </tr>
                    <tr>
                        <td>Other (In Progress / Pending)</td>
                        <td class="text-right text-bold">{{ number_format($otherOrders) }}</td>
                        <td class="text-right text-blue">{{ $report->total_orders > 0 ? number_format(($otherOrders / $report->total_orders) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td>Avg Fare (completed)</td>
                        <td colspan="2" class="text-right text-bold">₱{{ number_format($report->avg_fare, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Distance</td>
                        <td colspan="2" class="text-right text-bold">{{ number_format($report->total_distance_km, 1) }} km</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Revenue Details</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenueDetails as $item)
                    <tr>
                        <td class="capitalize">{{ str_replace('_', ' ', $item->revenue_type) }}</td>
                        <td class="text-right">{{ number_format($item->total_transactions) }}</td>
                        <td class="text-right text-bold">₱{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-gray">No revenue data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="report-footer">
            <p>Generated by LesGo Admin</p>
            <p>{{ date('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>