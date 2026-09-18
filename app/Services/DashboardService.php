<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentVerification;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\RatingReview;
use App\Models\SecurityEvent;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardService
{
    /**
     * Statuses treated as successfully paid regardless of provider vocabulary
     * (xendit/gcash/paymongo may store paid, settled, succeeded, completed, success...).
     */
    private const PAID_STATUSES = [
        'paid', 'settled', 'succeeded', 'completed', 'success',
        'captured', 'approved', 'fulfilled', 'received',
    ];
    private function safeRemember(string $key, int $ttl, callable $fetcher): mixed
    {
        $v2 = $key.':v2';
        try {
            $cached = Cache::get($v2);
            if ($cached !== null && ! is_string($cached)) {
                return $cached;
            }
            if (is_string($cached)) {
                Cache::forget($v2);
            }
            // bust poisoned v1
            try { Cache::forget($key); } catch (Throwable $e) {}
            $result = $fetcher();
            // guard: never cache a string
            if (is_string($result)) {
                return $result;
            }
            try { Cache::put($v2, $result, $ttl); } catch (Throwable $e) { report($e); }
            return $result;
        } catch (Throwable $e) {
            report($e);
            return $fetcher();
        }
    }

    /**
     * Revenue is recorded on orders in this system (orders.payment_status / actual_fare);
     * the payments table is usually empty. Fall back to payments only when no paid orders exist.
     */
    private function usesOrdersForRevenue(): bool
    {
        return Order::where('payment_status', 'paid')->exists();
    }

    private function revenueTotal(): float
    {
        if ($this->usesOrdersForRevenue()) {
            return (float) Order::where('payment_status', 'paid')
                ->sum(DB::raw('COALESCE(actual_fare, estimated_fare, 0)'));
        }

        return (float) Payment::whereIn('status', self::PAID_STATUSES)->sum('amount');
    }

    public function getStats(): array
    {
        return $this->safeRemember('dashboard:stats', 600, fn () => $this->fetchStats());
    }

    private function fetchStats(): array
    {
        $paymentStatuses = implode(',', array_map(fn ($s) => "'{$s}'", self::PAID_STATUSES));

        $row = (object) DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM orders) AS total_orders,
                (SELECT COUNT(*) FROM partners) AS total_partners,
                (SELECT COUNT(*) FROM driver_profiles) AS total_drivers,
                (SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress','waiting_internal')) AS open_tickets,
                (SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending_orders,
                (SELECT COUNT(*) FROM orders WHERE status = 'completed') AS completed_orders,
                (SELECT COUNT(*) FROM users WHERE is_active = true) AS active_users,
                (SELECT COUNT(*) FROM partners WHERE is_open = true) AS active_partners,
                (SELECT COUNT(*) FROM document_verifications WHERE status IN ('pending','under_review')) AS pending_verifications,
                (SELECT COUNT(*) FROM ratings_reviews WHERE status IN ('pending','flagged')) AS pending_reviews,
                (SELECT COUNT(*) FROM security_events WHERE is_resolved = false) AS open_security_events,
                (SELECT COUNT(*) FROM orders WHERE payment_status = 'paid') AS paid_order_count,
                (SELECT COALESCE(SUM(COALESCE(actual_fare, estimated_fare, 0)), 0) FROM orders WHERE payment_status = 'paid') AS paid_order_revenue,
                (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ({$paymentStatuses})) AS payment_revenue
        ");

        return [
            'total_users' => (int) $row->total_users,
            'total_orders' => (int) $row->total_orders,
            'total_partners' => (int) $row->total_partners,
            'total_drivers' => (int) $row->total_drivers,
            'open_tickets' => (int) $row->open_tickets,
            'total_revenue' => (float) ((int) $row->paid_order_count > 0 ? $row->paid_order_revenue : $row->payment_revenue),
            'pending_orders' => (int) $row->pending_orders,
            'completed_orders' => (int) $row->completed_orders,
            'active_users' => (int) $row->active_users,
            'active_partners' => (int) $row->active_partners,
            'pending_verifications' => (int) $row->pending_verifications,
            'pending_reviews' => (int) $row->pending_reviews,
            'open_security_events' => (int) $row->open_security_events,
        ];
    }

    public function getRecentOrders(int $limit = 10)
    {
        return $this->safeRemember("dashboard:recent_orders:{$limit}", 120, fn () => Order::with(['customer', 'partner'])->latest()->take($limit)->get());
    }

    public function getRecentUsers(int $limit = 10)
    {
        return $this->safeRemember("dashboard:recent_users:{$limit}", 120, fn () => User::latest()->take($limit)->get());
    }

    public function getDailyRevenue(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        return $this->safeRemember("dashboard:daily_revenue:{$days}", 600, function () use ($startDate) {
            if ($this->usesOrdersForRevenue()) {
                return Order::where('payment_status', 'paid')
                    ->where('created_at', '>=', $startDate)
                    ->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(COALESCE(actual_fare, estimated_fare, 0)) as total'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('date')->orderBy('date')->get();
            }

            return Payment::whereIn('status', self::PAID_STATUSES)
                ->where('created_at', '>=', $startDate)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')->orderBy('date')->get();
        });
    }

    public function getOrderStatusDistribution()
    {
        return $this->safeRemember('dashboard:order_status_dist', 600, fn () => Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->get());
    }

    public function getDailyUsers(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        return $this->safeRemember("dashboard:daily_users:{$days}", 600, fn () => User::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')->orderBy('date')->get());
    }

    public function getTopPartners(int $days = 7, int $limit = 5)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        return $this->safeRemember("dashboard:top_partners:{$days}", 600, function () use ($startDate, $limit) {
            // Partner orders are resolved via menus (orders → lesbuy_items → menu_items.partner_id),
            // since the API never populates orders.partner_id.
            $partnerExpr = 'COALESCE(orders.partner_id, menu_items.partner_id, menu_categories.partner_id, services.partner_id)';

            $rows = Order::query()
                ->leftJoin('lesbuy_items', 'lesbuy_items.order_id', '=', 'orders.id')
                ->leftJoin('menu_items', 'menu_items.id', '=', 'lesbuy_items.menu_item_id')
                ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id')
                ->leftJoin('services', 'services.id', '=', 'orders.service_id')
                ->select(
                    DB::raw($partnerExpr.' as partner_id'),
                    DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                    DB::raw('SUM(COALESCE(orders.actual_fare, orders.estimated_fare, 0)) as revenue')
                )
                ->where('orders.created_at', '>=', $startDate)
                ->whereNotNull(DB::raw($partnerExpr))
                ->groupBy(DB::raw($partnerExpr))
                ->orderByDesc('order_count')
                ->limit($limit)
                ->get()
                ->filter(fn ($row) => is_object($row) && ! empty($row->partner_id))
                ->values();

            $ids = $rows->pluck('partner_id')->filter()->unique()->values();
            $partners = Partner::with('user')->whereIn('id', $ids)->get()->keyBy('id');

            return $rows->map(fn ($row) => [
                'partner_id' => $row->partner_id,
                'order_count' => $row->order_count,
                'revenue' => $row->revenue,
                'partner' => $partners->get((int) $row->partner_id),
            ])->values();
        });
    }
}
