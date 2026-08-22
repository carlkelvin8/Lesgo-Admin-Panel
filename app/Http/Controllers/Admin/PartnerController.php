<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    use SearchEscaping;
    public function index(Request $request)
    {
        $query = Partner::with('user');

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_open')) {
            $query->where('is_open', $request->is_open === '1');
        }

        $partners = $query->latest()->paginate(20)->withQueryString();

        return view('admin.partners.index', compact('partners'));
    }

    public function show(Partner $partner)
    {
        $partner->load(['user', 'services', 'orders' => function ($q) {
            $q->latest()->take(10);
        }]);

        return view('admin.partners.show', compact('partner'));
    }

    public function create()
    {
        $users = User::where('role', 'partner')->whereDoesntHave('partner')->get();
        return view('admin.partners.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['status'] = 'pending';

        Partner::create($validated);

        return redirect()->route('admin.partners.index')
            ->with('success', 'Partner created successfully.');
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.edit', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:pending,approved,rejected,suspended',
            'is_open' => 'boolean',
            'is_featured' => 'boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $partner->update($validated);

        return redirect()->route('admin.partners.show', $partner)
            ->with('success', 'Partner updated successfully.');
    }

    public function toggleStatus(Partner $partner)
    {
        $partner->update(['is_open' => !$partner->is_open]);

        $status = $partner->is_open ? 'opened' : 'closed';
        return redirect()->back()->with('success', "Partner {$status} successfully.");
    }

    public function destroy(Partner $partner)
    {
        $hasOrders = $partner->orders()->exists();
        $hasDrivers = \App\Models\DriverProfile::where('partner_id', $partner->id)->exists();

        if ($hasOrders || $hasDrivers) {
            return redirect()->back()->with('error', 'Cannot delete partner with existing orders or drivers. Remove them first.');
        }

        $partner->delete();

        return redirect()->route('admin.partners.index')
            ->with('success', 'Partner deleted successfully.');
    }
}
