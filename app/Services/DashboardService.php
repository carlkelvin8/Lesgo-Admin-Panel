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

class DashboardService
{
    public function getStats(): array
    {
        return Cache::remember('dashboard:stats', 300, function () {
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
        });
    }

    public function getRecentOrders(int $limit = 10)
    {
        return Cache::remember("dashboard:recent_orders:{$limit}", 60, function () use ($limit) {
            return Order::with(['customer', 'partner'])
                ->latest()
                ->take($limit)
                ->get();
        });
    }

    public function getRecentUsers(int $limit = 10)
    {
        return Cache::remember("dashboard:recent_users:{$limit}", 60, function () use ($limit) {
            return User::latest()->take($limit)->get();
        });
    }

    public function getDailyRevenue(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        return Cache::remember("dashboard:daily_revenue:{$days}", 300, function () use ($startDate) {
            return Payment::where('status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
    }

    public function getOrderStatusDistribution()
    {
        return Cache::remember('dashboard:order_status_dist', 300, function () {
            return Order::select('status', DB::raw('count(*) as total'))
                ->whereIn('status', ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])
                ->groupBy('status')
                ->get();
        });
    }

    public function getDailyUsers(int $days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        return Cache::remember("dashboard:daily_users:{$days}", 300, function () use ($startDate) {
            return User::where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
    }

    public function getTopPartners(int $days = 7, int $limit = 5)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        return Cache::remember("dashboard:top_partners:{$days}", 300, function () use ($startDate, $limit) {
            return Order::with('partner')
                ->where('created_at', '>=', $startDate)
                ->select('partner_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(actual_fare) as revenue'))
                ->groupBy('partner_id')
                ->orderByDesc('order_count')
                ->take($limit)
                ->get();
        });
    }
}
