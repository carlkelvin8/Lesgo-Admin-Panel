<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationFeePayment;
use App\Models\SecuritySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationFeeController extends Controller
{
    public function index(Request $request)
    {
        $query = RegistrationFeePayment::with(['user:id,name,email', 'approver:id,name', 'waivedBy:id,name']);

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('application_status')) {
            $query->where('application_status', $request->application_status);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('paymongo_reference', 'like', "%{$search}%")
                  ->orWhere('paymongo_checkout_id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($qq) => $qq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $fees = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => RegistrationFeePayment::count(),
            'rider' => RegistrationFeePayment::where('account_type', 'rider')->count(),
            'merchant' => RegistrationFeePayment::where('account_type', 'merchant')->count(),
            'paid' => RegistrationFeePayment::where('payment_status', 'paid')->count(),
            'waived' => RegistrationFeePayment::where('payment_status', 'waived')->count(),
            'approved' => RegistrationFeePayment::where('application_status', 'approved')->count(),
            'active' => RegistrationFeePayment::where('is_active', true)->count(),
            'restricted' => RegistrationFeePayment::where('is_active', false)->count(),
        ];

        $riderPackagePrices = [
            'basic' => (float) SecuritySetting::value('rider.package.price.basic', 999),
            'advance' => (float) SecuritySetting::value('rider.package.price.advance', 1999),
            'pro' => (float) SecuritySetting::value('rider.package.price.pro', 2999),
        ];

        return view('admin.registration-fees.index', compact('fees', 'stats', 'riderPackagePrices'));
    }

    public function updateRiderPrices(Request $request)
    {
        $validated = $request->validate([
            'basic' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'advance' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'elite' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $prices = [
            'basic' => round((float) $validated['basic'], 2),
            'advance' => round((float) $validated['advance'], 2),
            'pro' => round((float) $validated['elite'], 2),
        ];

        DB::transaction(function () use ($prices, $request) {
            foreach ($prices as $tier => $price) {
                SecuritySetting::query()->updateOrCreate(
                    ['setting_key' => "rider.package.price.{$tier}"],
                    [
                        'setting_value' => number_format($price, 2, '.', ''),
                        'data_type' => 'string',
                        'description' => 'One-time rider package registration price in PHP',
                        'category' => 'rider_packages',
                        'is_sensitive' => false,
                        'requires_restart' => false,
                        'updated_by' => (string) $request->user()->id,
                    ],
                );
            }

            RegistrationFeePayment::query()
                ->where('account_type', RegistrationFeePayment::TYPE_RIDER)
                ->whereNotIn('payment_status', [
                    RegistrationFeePayment::PAYMENT_PAID,
                    RegistrationFeePayment::PAYMENT_WAIVED,
                ])
                ->with('user.driverProfile')
                ->each(function (RegistrationFeePayment $fee) use ($prices) {
                    $rawTier = strtolower(trim((string) ($fee->user?->driverProfile?->package_tier ?? 'basic')));
                    $tier = match ($rawTier) {
                        'advance', 'advanced', 'premium' => 'advance',
                        'pro', 'pro_rider', 'professional', 'elite' => 'pro',
                        default => 'basic',
                    };
                    $fee->update(['amount' => $prices[$tier]]);
                });
        });

        return back()->with('success', 'Rider package prices updated successfully. Unpaid rider fees were synchronized.');
    }

    public function show(RegistrationFeePayment $registrationFee)
    {
        $registrationFee->load(['user', 'approver', 'waivedBy']);
        return view('admin.registration-fees.show', ['fee' => $registrationFee]);
    }

    public function approve(Request $request, RegistrationFeePayment $registrationFee)
    {
        \App\Services\RegistrationFeeService::approveApplication($registrationFee, $request->user());
        return back()->with('success', $registrationFee->fresh()->is_active ? 'Approved and activated (fee paid/waived + approved).' : 'Approved – account remains restricted until fee is paid or waived.');
    }

    public function waive(Request $request, RegistrationFeePayment $registrationFee)
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:1000']);
        try {
            $updated = \App\Services\RegistrationFeeService::waiveAndApprove($registrationFee, $request->user(), $validated['reason'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', $updated->is_active
            ? 'Registration fee waived; application approved and account activated.'
            : 'Registration fee waived and application approved, but the account could not be activated.');
    }

    public function reject(Request $request, RegistrationFeePayment $registrationFee)
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:1000']);
        \App\Services\RegistrationFeeService::rejectApplication($registrationFee, $request->user(), $validated['reason'] ?? null);
        return back()->with('success', 'Application rejected – account remains restricted.');
    }
}
