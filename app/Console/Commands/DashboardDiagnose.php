<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardDiagnose extends Command
{
    protected $signature = 'admin:dashboard-diagnose';

    protected $description = 'Print the dataset counts behind every dashboard widget to diagnose empty charts.';

    private const PAID_STATUSES = [
        'paid', 'settled', 'succeeded', 'completed', 'success',
        'captured', 'approved', 'fulfilled', 'received',
    ];

    public function handle(): int
    {
        $now = Carbon::now();
        $week = $now->copy()->subDays(6)->startOfDay();
        $paid = self::PAID_STATUSES;

        $this->info('Database: '.config('database.connections.'.config('database.default').'.database'));

        $this->line('');
        $this->line('--- Stat cards (getStats) ---');
        $this->show('users (total)', User::count());
        $this->show('orders (total)', Order::count());
        $this->show('partners (total)', Partner::count());
        $this->show('orders pending', Order::where('status', 'pending')->count());
        $this->show('orders paid', Order::where('payment_status', 'paid')->count());
        $this->show('revenue from paid orders', Order::where('payment_status', 'paid')->sum(DB::raw('COALESCE(actual_fare, estimated_fare, 0)')));
        $this->show('payments rows (legacy)', Payment::count());
        $this->show('payments paid + sum', Payment::whereIn('status', $paid)->count().' / '.Payment::whereIn('status', $paid)->sum('amount'));

        $this->line('');
        $this->line('--- Revenue Overview (paid orders in the last 7 days) ---');
        $this->show('last 7d paid orders', Order::where('payment_status', 'paid')->where('created_at', '>=', $week)->count());
        $this->show('last 7d paid order revenue', Order::where('payment_status', 'paid')->where('created_at', '>=', $week)->sum(DB::raw('COALESCE(actual_fare, estimated_fare, 0)')));
        $this->show('payments per status', Payment::select('status', DB::raw('count(*) as total'))->groupBy('status')->orderByDesc('total')->get()
            ->map(fn ($r) => $r->status.'='.$r->total)->implode(', '));
        $this->show('order payment_status per status', Order::select('payment_status', DB::raw('count(*) as total'))->groupBy('payment_status')->orderByDesc('total')->get()
            ->map(fn ($r) => $r->payment_status.'='.$r->total)->implode(', '));
        $latestPaid = Order::where('payment_status', 'paid')->max('created_at');
        $this->line('       latest paid order created_at: '.($latestPaid ?? 'none'));

        $this->line('');
        $this->line('--- Order Status Distribution ---');
        foreach (Order::select('status', DB::raw('count(*) as total'))->groupBy('status')->orderByDesc('total')->get() as $row) {
            $this->line(sprintf('       %-14s %d', $row->status, $row->total));
        }
        $latestOrder = Order::max('created_at');
        $this->line('       latest order created_at: '.($latestOrder ?? 'none'));

        $this->line('');
        $this->line('--- User Growth: users in the last 7 days ---');
        $this->show('last 7d new users', User::where('created_at', '>=', $week)->count());
        $latestUser = User::max('created_at');
        $this->line('       latest user created_at: '.($latestUser ?? 'none'));

        $this->line('');
        $this->line('--- Top Partners (via lesbuy_items -> menu_items.partner_id) ---');
        $partnerExpr = 'COALESCE(orders.partner_id, menu_items.partner_id, menu_categories.partner_id, services.partner_id)';
        $rows = DB::table('orders')
            ->leftJoin('lesbuy_items', 'lesbuy_items.order_id', '=', 'orders.id')
            ->leftJoin('menu_items', 'menu_items.id', '=', 'lesbuy_items.menu_item_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id')
            ->leftJoin('services', 'services.id', '=', 'orders.service_id')
            ->select(DB::raw($partnerExpr.' as pid'), DB::raw('COUNT(DISTINCT orders.id) as orders'))
            ->groupBy(DB::raw($partnerExpr))
            ->orderByDesc('orders')
            ->limit(5)
            ->get();
        foreach ($rows as $row) {
            $name = $row->pid ? data_get(Partner::find($row->pid), 'name', '(missing partner)') : '(no partner)';
            $this->line(sprintf('       partner %-3s %-30s %d orders', $row->pid ?? '-', $name, $row->orders));
        }
        $this->show('orders with any partner link', DB::table('orders')
            ->leftJoin('lesbuy_items', 'lesbuy_items.order_id', '=', 'orders.id')
            ->leftJoin('menu_items', 'menu_items.id', '=', 'lesbuy_items.menu_item_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id')
            ->leftJoin('services', 'services.id', '=', 'orders.service_id')
            ->whereNotNull(DB::raw($partnerExpr))
            ->count(DB::raw('DISTINCT orders.id')));

        $this->line('');
        $this->line('--- Date/tz sanity ---');
        $this->line('       now on server: '.$now->toDateTimeString().' ('.$now->getTimezone().')');

        return self::SUCCESS;
    }

    private function show(string $label, $value): void
    {
        $this->line(sprintf('       %-40s %s', $label.':', $value));
    }
}