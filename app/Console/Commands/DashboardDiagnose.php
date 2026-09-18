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

    public function handle(): int
    {
        $now = Carbon::now();
        $week = $now->copy()->subDays(6)->startOfDay();

        $this->info('Database: '.config('database.connections.'.config('database.default').'.database'));

        $this->line('');
        $this->line('--- Stat cards (getStats) ---');
        $this->show('users (total)', User::count());
        $this->show('orders (total)', Order::count());
        $this->show('partners (total)', Partner::count());
        $this->show('orders pending', Order::where('status', 'pending')->count());
        $this->show('payments paid + sum', Payment::where('status', 'paid')->count().' / '.Payment::where('status', 'paid')->sum('amount'));

        $this->line('');
        $this->line('--- Revenue Overview: paid payments in the last 7 days ---');
        $this->show('last 7d paid payments', Payment::where('status', 'paid')->where(function ($q) use ($week) {
            $q->where('paid_at', '>=', $week)->orWhereNull('paid_at')->where('created_at', '>=', $week);
        })->count());
        $latestPaid = Payment::where('status', 'paid')->max('created_at');
        $this->line('       latest paid payment created_at: '.($latestPaid ?? 'none'));

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
        $rows = DB::table('orders')
            ->leftJoin('lesbuy_items', 'lesbuy_items.order_id', '=', 'orders.id')
            ->leftJoin('menu_items', 'menu_items.id', '=', 'lesbuy_items.menu_item_id')
            ->select(DB::raw('COALESCE(orders.partner_id, menu_items.partner_id) as pid'), DB::raw('COUNT(DISTINCT orders.id) as orders'))
            ->groupBy(DB::raw('COALESCE(orders.partner_id, menu_items.partner_id)'))
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
            ->whereNotNull(DB::raw('COALESCE(orders.partner_id, menu_items.partner_id)'))
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