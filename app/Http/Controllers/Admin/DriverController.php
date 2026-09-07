<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerification;
use App\Models\DriverProfile;
use App\Models\Partner;
use App\Models\User;
use App\Services\CascadeEntityDeletionService;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    use SearchEscaping;
    public function index(Request $request)
    {
        $query = DriverProfile::with('user');

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
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
        $driver->load(['user.documentVerifications' => fn ($q) => $q->latest('submitted_at'), 'partner']);

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
            'license_expiry_date' => 'nullable|date',
            'vehicle_type' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:50',
            'package_tier' => 'nullable|string|max:100',
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('id_document')) {
            $validated['id_document_path'] = $request->file('id_document')->store('driver-documents', config('filesystems.default') === 's3' ? 's3' : 'public');
        }
        unset($validated['id_document']);

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
            'license_expiry_date' => 'nullable|date',
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

    public function destroy(DriverProfile $driver, CascadeEntityDeletionService $deletionService)
    {
        $deletionService->deleteDriver($driver);

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Rider and all linked data deleted successfully.');
    }

    public function bulkDestroy(Request $request, CascadeEntityDeletionService $deletionService)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:driver_profiles,id'],
        ]);

        $drivers = DriverProfile::query()->whereIn('id', $validated['ids'])->get();
        \Illuminate\Support\Facades\DB::transaction(function () use ($drivers, $deletionService) {
            foreach ($drivers as $driver) {
                $deletionService->deleteDriver($driver);
            }
        });

        return back()->with('success', $drivers->count().' rider(s) and all linked data deleted.');
    }

    public function storeDocument(Request $request, DriverProfile $driver)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:100',
            'document_number' => 'nullable|string|max:255',
            'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'description' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date',
        ]);

        $path = $request->file('document_file')->store('driver-documents/'.$driver->id, config('filesystems.default') === 's3' ? 's3' : 'public');
        $url = Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($path);

        DocumentVerification::create([
            'user_id' => $driver->user_id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'document_urls' => [$url],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'expires_at' => $validated['expires_at'] ?? null,
            'submitted_at' => now(),
        ]);

        return redirect()->route('admin.drivers.show', $driver)->with('success', 'Document added successfully.');
    }

    public function updateDocuments(Request $request, DriverProfile $driver)
    {
        $validated = $request->validate([
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'remove_id_document' => 'nullable|boolean',
        ]);

        $data = [];
        if ($request->hasFile('id_document')) {
            $path = $request->file('id_document')->store('driver-documents/'.$driver->id, config('filesystems.default') === 's3' ? 's3' : 'public');
            $data['id_document_path'] = $path;
        } elseif ($request->boolean('remove_id_document')) {
            $data['id_document_path'] = null;
        }

        if ($request->hasFile('documents')) {
            $existing = $driver->documents ?? [];
            foreach ($request->file('documents') as $key => $file) {
                if (!$file) continue;
                $path = $file->store('driver-documents/'.$driver->id, config('filesystems.default') === 's3' ? 's3' : 'public');
                $url = Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($path);
                $existing[$key] = $url;
            }
            $data['documents'] = $existing;
        }

        if (!empty($data)) {
            $driver->update($data);
        }

        return redirect()->route('admin.drivers.show', $driver)->with('success', 'Driver documents updated.');
    }

}
