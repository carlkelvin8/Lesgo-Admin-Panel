<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationFeePayment;
use Illuminate\Http\Request;

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

        return view('admin.registration-fees.index', compact('fees', 'stats'));
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
            $updated = \App\Services\RegistrationFeeService::waiveFee($registrationFee, $request->user(), $validated['reason'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', $updated->is_active
            ? 'Registration fee waived; approved account activated.'
            : 'Registration fee waived; account remains restricted pending approval.');
    }

    public function reject(Request $request, RegistrationFeePayment $registrationFee)
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:1000']);
        \App\Services\RegistrationFeeService::rejectApplication($registrationFee, $request->user(), $validated['reason'] ?? null);
        return back()->with('success', 'Application rejected – account remains restricted.');
    }
}
