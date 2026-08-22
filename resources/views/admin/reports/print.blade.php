<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - {{ $date ?? date('Y-m-d') }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        @media print {
            body { margin: 0; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
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
            padding-bottom: 20px;
            margin-bottom: 28px;
        }
        .report-header h1 { font-size: 22px; color: #1e1b4b; margin-bottom: 4px; }
        .report-header p { color: #6b7280; font-size: 13px; }
        .section { margin-bottom: 28px; }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e9d5ff;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase; }
        td { font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .metric-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .metric-card .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .metric-card .value {
            font-size: 22px;
            font-weight: 700;
            color: #7c3aed;
        }
        .report-footer {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
        }
        @media print {
            .metrics-grid { grid-template-columns: repeat(4, 1fr); }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1>LesGo Daily Report</h1>
            <p>Report Date: {{ $date ?? date('F j, Y') }}</p>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">Total Users</div>
                <div class="value">{{ number_format($totalUsers ?? 0) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Active Partners</div>
                <div class="value">{{ number_format($activePartners ?? 0) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Orders</div>
                <div class="value">{{ number_format($totalOrders ?? 0) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Open Tickets</div>
                <div class="value">{{ number_format($openTickets ?? 0) }}</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Revenue Details</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenueDetails ?? [] as $item)
                    <tr>
                        <td>{{ $item['category'] ?? 'N/A' }}</td>
                        <td>${{ number_format($item['amount'] ?? 0, 2) }}</td>
                        <td>{{ $item['change'] ?? '0%' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #9ca3af;">No revenue data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Order Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Completed Orders</td>
                        <td>{{ number_format($completedOrders ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Pending Orders</td>
                        <td>{{ number_format($pendingOrders ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Cancelled Orders</td>
                        <td>{{ number_format($cancelledOrders ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Total Revenue</td>
                        <td>${{ number_format($totalRevenue ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">User Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>New Registrations</td>
                        <td>{{ number_format($newRegistrations ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Active Sessions</td>
                        <td>{{ number_format($activeSessions ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Average Session Duration</td>
                        <td>{{ $avgSessionDuration ?? '0m' }}</td>
                    </tr>
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