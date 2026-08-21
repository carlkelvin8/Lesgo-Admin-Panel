<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverProfile::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('package_tier')) {
            $query->where('package_tier', $request->package_tier);
        }

        $drivers = $query->latest()->paginate(20)->withQueryString();

        return view('admin.drivers.index', compact('drivers'));
    }

    public function show(DriverProfile $driver)
    {
        $driver->load(['user', 'partner']);

        return view('admin.drivers.show', compact('driver'));
    }

    public function create()
    {
        $users = User::where('role', 'driver')->whereDoesntHave('driverProfile')->get();
        $partners = Partner::all();

        return view('admin.drivers.create', compact('users', 'partners'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'partner_id' => 'nullable|exists:partners,id',
            'license_number' => 'nullable|string|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:50',
            'package_tier' => 'nullable|string|max:100',
        ]);

        $validated['status'] = 'pending';

        DriverProfile::create($validated);

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver profile created successfully.');
    }

    public function edit(DriverProfile $driver)
    {
        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(Request $request, DriverProfile $driver)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,active,inactive,suspended',
            'license_number' => 'nullable|string|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:50',
            'package_tier' => 'nullable|string|max:100',
        ]);

        $driver->update($validated);

        return redirect()->route('admin.drivers.show', $driver)
            ->with('success', 'Driver profile updated successfully.');
    }

    public function toggleStatus(DriverProfile $driver)
    {
        $newStatus = $driver->status === 'active' ? 'inactive' : 'active';
        $driver->update(['status' => $newStatus]);

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', "Driver {$newStatus} successfully.");
    }
}
