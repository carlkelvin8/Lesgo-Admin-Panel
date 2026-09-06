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

    public function getStats(): array
    {
        return $this->safeRemember('dashboard:stats', 300, fn () => $this->fetchStats());
    }

    private function fetchStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_orders' => Order::count(),
            'total_partners' => Partner::count(),
            'total_drivers' => DriverProfile::count(),
            'open_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress', 'waiting_internal'])->count(),
            'total_revenue' => Payment::where('status', 'paid')->sum('amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'active_users' => User::where('is_active', true)->count(),
            'active_partners' => Partner::where('is_open', true)->count(),
            'pending_verifications' => DocumentVerification::whereIn('status', ['pending', 'under_review'])->count(),
            'pending_reviews' => RatingReview::whereIn('status', ['pending', 'flagged'])->count(),
            'open_security_events' => SecurityEvent::where('is_resolved', false)->count(),
        ];
    }

    public function getRecentOrders(int $limit = 10)
    {
        return $this->safeRemember("dashboard:recent_orders:{$limit}", 60, fn () => Order::with(['customer', 'partner'])->latest()->take($limit)->get());
    }

    public function getRecentUsers(int $limit = 10)
    {
        return $this->safeRemember("dashboard:recent_users:{$limit}", 60, fn () => User::latest()->take($limit)->get());
    }

    public function getDailyRevenue(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        return $this->safeRemember("dashboard:daily_revenue:{$days}", 300, fn () => Payment::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')->get());
    }

    public function getOrderStatusDistribution()
    {
        return $this->safeRemember('dashboard:order_status_dist', 300, fn () => Order::select('status', DB::raw('count(*) as total'))
            ->whereIn('status', ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])
            ->groupBy('status')->get());
    }

    public function getDailyUsers(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        return $this->safeRemember("dashboard:daily_users:{$days}", 300, fn () => User::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')->orderBy('date')->get());
    }

    public function getTopPartners(int $days = 7, int $limit = 5)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        return $this->safeRemember("dashboard:top_partners:{$days}", 300, fn () => Order::with(['partner.user'])
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('partner_id')
            ->select('partner_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(actual_fare) as revenue'))
            ->groupBy('partner_id')
            ->orderByDesc('order_count')
            ->take($limit)
            ->get()
            ->filter(fn ($row) => is_object($row) && ! empty($row->partner_id))
            ->values());
    }
}
