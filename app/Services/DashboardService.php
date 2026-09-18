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
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardService
{
    /**
     * Statuses treated as successfully paid regardless of provider vocabulary.
     */
    private const PAID_STATUSES = [
        'paid', 'settled', 'succeeded', 'completed', 'success',
        'captured', 'approved', 'fulfilled', 'received',
    ];

    /**
     * Cached per-request flag so usesOrdersForRevenue() never fires twice.
     */
    private ?bool $revenueSource = null;

    // ──────────────────────────────────────────────────────────────────────────
    //  Public API — called by the controller
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Load all dashboard data in one shot using concurrent DB calls.
     * Each closure is executed in parallel; results are keyed by array position.
     */
    public function getAll(int $days = 7): array
    {
        $cached = Cache::get('dashboard:all:'.$days);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $workers = [
            fn () => $this->fetchStats(),
            fn () => $this->fetchRecentOrders(),
            fn () => $this->fetchRecentUsers(),
            fn () => $this->fetchDailyRevenue($days),
            fn () => $this->fetchOrderStatusDistribution(),
            fn () => $this->fetchDailyUsers($days),
            fn () => $this->fetchTopPartners($days),
        ];

        try {
            [$stats, $recentOrders, $recentUsers, $dailyRevenue, $orderStatusDist, $dailyUsers, $topPartners]
                = Concurrency::run($workers);
        } catch (Throwable $e) {
            // A subprocess worker hiccup (eg. sqlite :memory: in tests) must never
            // take the whole dashboard down — fall back to sequential queries.
            report($e);
            $workerResults = array_map(fn (callable $worker) => $worker(), $workers);
            [$stats, $recentOrders, $recentUsers, $dailyRevenue, $orderStatusDist, $dailyUsers, $topPartners]
                = $workerResults;
        }

        $result = compact(
            'stats', 'recentOrders', 'recentUsers',
            'dailyRevenue', 'orderStatusDist', 'dailyUsers', 'topPartners'
        );

        // Store combined payload — shorter TTL (120 s) so recent data stays fresh,
        // while individual heavy queries benefit from the same warm cache.
        try {
            Cache::put('dashboard:all:'.$days, $result, 120);
        } catch (Throwable $e) {
            report($e);
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Individual public fetchers (kept for direct use / cache warming)
    // ──────────────────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        return Cache::remember('dashboard:stats', 600, fn () => $this->fetchStats());
    }

    public function getRecentOrders(int $limit = 10)
    {
        return Cache::remember("dashboard:recent_orders:{$limit}", 120, fn () => $this->fetchRecentOrders($limit));
    }

    public function getRecentUsers(int $limit = 10)
    {
        return Cache::remember("dashboard:recent_users:{$limit}", 120, fn () => $this->fetchRecentUsers($limit));
    }

    public function getDailyRevenue(int $days = 7)
    {
        return Cache::remember("dashboard:daily_revenue:{$days}", 600, fn () => $this->fetchDailyRevenue($days));
    }

    public function getOrderStatusDistribution()
    {
        return Cache::remember('dashboard:order_status_dist', 600, fn () => $this->fetchOrderStatusDistribution());
    }

    public function getDailyUsers(int $days = 7)
    {
        return Cache::remember("dashboard:daily_users:{$days}", 600, fn () => $this->fetchDailyUsers($days));
    }

    public function getTopPartners(int $days = 7, int $limit = 5)
    {
        return Cache::remember("dashboard:top_partners:{$days}", 600, fn () => $this->fetchTopPartners($days, $limit));
    }

    /** Bust all dashboard cache keys (call from cache-warm command after repopulating). */
    public function flushCache(): void
    {
        $keys = [
            'dashboard:stats',
            'dashboard:order_status_dist',
        ];
        foreach ([7, 14, 30, 90] as $d) {
            $keys[] = "dashboard:all:{$d}";
            $keys[] = "dashboard:daily_revenue:{$d}";
            $keys[] = "dashboard:daily_users:{$d}";
            $keys[] = "dashboard:top_partners:{$d}";
        }
        foreach ([5, 10, 20] as $l) {
            $keys[] = "dashboard:recent_orders:{$l}";
            $keys[] = "dashboard:recent_users:{$l}";
        }
        foreach ($keys as $key) {
            try { Cache::forget($key); } catch (Throwable) {}
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Private fetch methods (no caching — caller decides)
    // ──────────────────────────────────────────────────────────────────────────

    private function fetchStats(): array
    {
        $paymentStatuses = implode(',', array_map(fn ($s) => "'{$s}'", self::PAID_STATUSES));

        $row = (object) DB::selectOne("
            SELECT
                (SELECT COUNT(*)  FROM users)                                                      AS total_users,
                (SELECT COUNT(*)  FROM orders)                                                     AS total_orders,
                (SELECT COUNT(*)  FROM partners)                                                   AS total_partners,
                (SELECT COUNT(*)  FROM driver_profiles)                                            AS total_drivers,
                (SELECT COUNT(*)  FROM support_tickets
                    WHERE status IN ('open','in_progress','waiting_internal'))                     AS open_tickets,
                (SELECT COUNT(*)  FROM orders WHERE status = 'pending')                           AS pending_orders,
                (SELECT COUNT(*)  FROM orders WHERE status = 'completed')                         AS completed_orders,
                (SELECT COUNT(*)  FROM users WHERE is_active = true)                              AS active_users,
                (SELECT COUNT(*)  FROM partners WHERE is_open = true)                             AS active_partners,
                (SELECT COUNT(*)  FROM document_verifications
                    WHERE status IN ('pending','under_review'))                                    AS pending_verifications,
                (SELECT COUNT(*)  FROM ratings_reviews
                    WHERE status IN ('pending','flagged'))                                         AS pending_reviews,
                (SELECT COUNT(*)  FROM security_events WHERE is_resolved = false)                 AS open_security_events,
                (SELECT COUNT(*)  FROM orders WHERE payment_status = 'paid')                      AS paid_order_count,
                (SELECT COALESCE(SUM(COALESCE(actual_fare, estimated_fare, 0)), 0)
                    FROM orders WHERE payment_status = 'paid')                                    AS paid_order_revenue,
                (SELECT COALESCE(SUM(amount), 0)
                    FROM payments WHERE status IN ({$paymentStatuses}))                           AS payment_revenue
        ");

        return [
            'total_users'            => (int)   $row->total_users,
            'total_orders'           => (int)   $row->total_orders,
            'total_partners'         => (int)   $row->total_partners,
            'total_drivers'          => (int)   $row->total_drivers,
            'open_tickets'           => (int)   $row->open_tickets,
            'total_revenue'          => (float) ((int) $row->paid_order_count > 0
                                                    ? $row->paid_order_revenue
                                                    : $row->payment_revenue),
            'pending_orders'         => (int)   $row->pending_orders,
            'completed_orders'       => (int)   $row->completed_orders,
            'active_users'           => (int)   $row->active_users,
            'active_partners'        => (int)   $row->active_partners,
            'pending_verifications'  => (int)   $row->pending_verifications,
            'pending_reviews'        => (int)   $row->pending_reviews,
            'open_security_events'   => (int)   $row->open_security_events,
        ];
    }

    private function fetchRecentOrders(int $limit = 10)
    {
        return Order::with(['customer:id,name,email,profile_picture,profile_photo_url', 'partner:id,name,logo_url'])
            ->latest()
            ->take($limit)
            ->get();
    }

    private function fetchRecentUsers(int $limit = 10)
    {
        return User::latest()->take($limit)->get();
    }

    private function fetchDailyRevenue(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        if ($this->usesOrdersForRevenue()) {
            return Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(COALESCE(actual_fare, estimated_fare, 0)) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        return Payment::whereIn('status', self::PAID_STATUSES)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function fetchOrderStatusDistribution()
    {
        return Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
    }

    private function fetchDailyUsers(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        return User::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Top partners by order count.
     *
     * Strategy:
     *  1. orders.partner_id is the canonical source — populated when an order
     *     belongs to a merchant/food partner.
     *  2. For orders that lack partner_id (lesgo-buy / checklist orders), we
     *     fall back to menu_items.partner_id via lesbuy_items.
     *  3. A final GROUP BY on the resolved partner_id gives us the ranking.
     *
     * This avoids the previous four-table correlated COALESCE which could
     * resolve to ambiguous nulls and mask real partners.
     */
    private function fetchTopPartners(int $days = 7, int $limit = 5): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        // Step 1 — orders that have a direct partner_id
        $directRows = DB::table('orders')
            ->select(
                'partner_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(COALESCE(actual_fare, estimated_fare, 0)) as revenue')
            )
            ->whereNotNull('partner_id')
            ->where('created_at', '>=', $startDate)
            ->groupBy('partner_id');

        // Step 2 — orders without partner_id, resolved via lesbuy_items → menu_items
        $indirectRows = DB::table('orders')
            ->join('lesbuy_items', 'lesbuy_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'menu_items.id', '=', 'lesbuy_items.menu_item_id')
            ->select(
                'menu_items.partner_id',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(COALESCE(orders.actual_fare, orders.estimated_fare, 0)) as revenue')
            )
            ->whereNull('orders.partner_id')
            ->whereNotNull('menu_items.partner_id')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('menu_items.partner_id');

        // Step 3 — UNION and re-aggregate
        $rows = DB::table(DB::raw('('
                .$directRows->toSql()
                .' UNION ALL '
                .$indirectRows->toSql()
                .') as combined'))
            ->mergeBindings($directRows)
            ->mergeBindings($indirectRows)
            ->select(
                'partner_id',
                DB::raw('SUM(order_count) as order_count'),
                DB::raw('SUM(revenue) as revenue')
            )
            ->groupBy('partner_id')
            ->orderByDesc('order_count')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Batch-load partners with their user in a single query (no N+1)
        $partnerIds = $rows->pluck('partner_id')->filter()->unique()->values();
        $partners   = Partner::with('user:id,name,email')
            ->select(['id', 'name', 'logo_url', 'user_id', 'business_type', 'status'])
            ->whereIn('id', $partnerIds)
            ->get()
            ->keyBy('id');

        return $rows
            ->map(fn ($row) => [
                'partner_id'  => $row->partner_id,
                'order_count' => (int) $row->order_count,
                'revenue'     => (float) $row->revenue,
                'partner'     => $partners->get((int) $row->partner_id),
            ])
            ->filter(fn ($item) => $item['partner'] !== null)   // drop orphan IDs
            ->values()
            ->all();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns true when orders are the revenue source for this database.
     * Result is memoised for the lifetime of this service instance so the
     * COUNT query is never fired more than once per request.
     */
    private function usesOrdersForRevenue(): bool
    {
        return $this->revenueSource ??= Order::where('payment_status', 'paid')->exists();
    }
}
