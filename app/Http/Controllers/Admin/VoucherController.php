<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Voucher;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    use SearchEscaping;

    public function index(Request $request)
    {
        $query = Voucher::query();

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $vouchers = $query->latest()->paginate(20)->withQueryString();
        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function show(Voucher $voucher)
    {
        return view('admin.vouchers.show', compact('voucher'));
    }

    public function create()
    {
        $services = Service::where('is_active', true)->get();
        return view('admin.vouchers.create', compact('services'));
    }

    public function store(Request $request)
    {
        $request->merge(['code' => strtoupper(trim($request->input('code','')))]);
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_text' => 'nullable|string|max:100',
            'min_order' => 'nullable|string|max:100',
            'type' => 'required|in:percentage,fixed,free_delivery,buy_one_get_one',
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after_or_equal:today',
            'new_users_only' => 'nullable|boolean',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => 'integer|exists:services,id',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        $restrictions = [];
        if ($request->boolean('new_users_only')) {
            $restrictions['new_users_only'] = true;
        }
        if ($request->filled('max_uses_per_user')) {
            $restrictions['max_uses_per_user'] = (int) $request->max_uses_per_user;
        }
        $validated['user_restrictions'] = !empty($restrictions) ? $restrictions : null;
        unset($validated['new_users_only'], $validated['max_uses_per_user']);

        // applicable_services already validated
        if (empty($validated['applicable_services'])) {
            $validated['applicable_services'] = null;
        }

        Voucher::create($validated);

        return redirect()->route('admin.vouchers.index')->with('success', 'Promo created successfully.');
    }

    public function edit(Voucher $voucher)
    {
        $services = Service::where('is_active', true)->get();
        return view('admin.vouchers.edit', compact('voucher', 'services'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $request->merge(['code' => strtoupper(trim($request->input('code','')))]);
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_text' => 'nullable|string|max:100',
            'min_order' => 'nullable|string|max:100',
            'type' => 'required|in:percentage,fixed,free_delivery,buy_one_get_one',
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'new_users_only' => 'nullable|boolean',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => 'integer|exists:services,id',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $restrictions = [];
        if ($request->boolean('new_users_only')) {
            $restrictions['new_users_only'] = true;
        }
        if ($request->filled('max_uses_per_user')) {
            $restrictions['max_uses_per_user'] = (int) $request->max_uses_per_user;
        }
        $validated['user_restrictions'] = !empty($restrictions) ? $restrictions : null;
        unset($validated['new_users_only'], $validated['max_uses_per_user']);

        if (empty($validated['applicable_services'])) {
            $validated['applicable_services'] = null;
        }
        $validated['is_active'] = $request->boolean('is_active');

        $voucher->update($validated);

        return redirect()->route('admin.vouchers.show', $voucher)->with('success', 'Promo updated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('admin.vouchers.index')->with('success', 'Promo deleted successfully.');
    }

    public function toggleStatus(Voucher $voucher)
    {
        $voucher->update(['is_active' => !$voucher->is_active]);
        $status = $voucher->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Promo {$status} successfully.");
    }
}
