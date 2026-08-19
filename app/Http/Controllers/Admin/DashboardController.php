<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerification;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\RatingReview;
use App\Models\SecurityEvent;
use App\Models\SupportTicket;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
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

        $recent_orders = Order::with(['customer', 'partner'])
            ->latest()
            ->take(10)
            ->get();

        $recent_users = User::latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recent_orders', 'recent_users'));
    }
}
