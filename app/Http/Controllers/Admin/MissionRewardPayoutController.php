<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MissionRewardPayout;
use App\Models\MissionTemplate;
use Illuminate\Http\Request;

class MissionRewardPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = MissionRewardPayout::with(['rider:id,name,email', 'missionTemplate:id,title,reward_amount', 'mission:id,mission_date,is_completed']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('paymongo_reference', 'like', "%{$search}%")
                  ->orWhere('paymongo_transfer_id', 'like', "%{$search}%")
                  ->orWhereHas('rider', fn($qq) => $qq->where('name', 'like', "%{$search}%"));
            });
        }

        $payouts = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => MissionRewardPayout::count(),
            'pending' => MissionRewardPayout::where('status', 'pending')->count(),
            'processing' => MissionRewardPayout::where('status', 'processing')->count(),
            'successful' => MissionRewardPayout::where('status', 'successful')->count(),
            'failed' => MissionRewardPayout::where('status', 'failed')->count(),
        ];

        return view('admin.mission-rewards.index', compact('payouts', 'stats'));
    }

    public function show(MissionRewardPayout $missionReward)
    {
        $missionReward->load(['rider', 'mission', 'missionTemplate', 'driverProfile']);
        return view('admin.mission-rewards.show', [
            'payout' => $missionReward,
        ]);
    }

    public function retry(Request $request, MissionRewardPayout $missionReward)
    {
        if ($missionReward->status !== 'failed') {
            return back()->with('error', 'Only failed reimbursements can be retried.');
        }

        try {
            // Call API service if available, otherwise direct logic
            $serviceClass = 'App\\Services\\MissionRewardPayoutService';
            if (class_exists($serviceClass)) {
                $serviceClass::retryPayout($missionReward);
            } else {
                $missionReward->update(['status' => 'pending', 'failure_reason' => null]);
            }
            return back()->with('success', 'Reimbursement retry initiated via PayMongo.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Retry failed: ' . $e->getMessage());
        }
    }
}
