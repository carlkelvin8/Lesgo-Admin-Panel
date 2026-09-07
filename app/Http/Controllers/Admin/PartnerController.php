<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use App\Services\CascadeEntityDeletionService;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'legal_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|string|max:100',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_url'] = $request->file('logo')->store('partners/logos', config('filesystems.default') === 's3' ? 's3' : 'public');
        }
        if ($request->hasFile('cover_image')) {
            $validated['cover_image_url'] = $request->file('cover_image')->store('partners/covers', config('filesystems.default') === 's3' ? 's3' : 'public');
        }
        unset($validated['logo'], $validated['cover_image']);

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
            'legal_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:pending,approved,rejected,suspended',
            'is_open' => 'boolean',
            'is_featured' => 'boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_logo' => 'nullable|boolean',
            'remove_cover' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($partner->logo_url) try { Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($partner->logo_url); } catch (\Throwable $e) {}
            $validated['logo_url'] = $request->file('logo')->store('partners/logos', config('filesystems.default') === 's3' ? 's3' : 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($partner->logo_url) try { Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($partner->logo_url); } catch (\Throwable $e) {}
            $validated['logo_url'] = null;
        }
        if ($request->hasFile('cover_image')) {
            if ($partner->cover_image_url) try { Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($partner->cover_image_url); } catch (\Throwable $e) {}
            $validated['cover_image_url'] = $request->file('cover_image')->store('partners/covers', config('filesystems.default') === 's3' ? 's3' : 'public');
        } elseif ($request->boolean('remove_cover')) {
            if ($partner->cover_image_url) try { Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($partner->cover_image_url); } catch (\Throwable $e) {}
            $validated['cover_image_url'] = null;
        }
        unset($validated['logo'], $validated['cover_image'], $validated['remove_logo'], $validated['remove_cover']);

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

    public function destroy(Partner $partner, CascadeEntityDeletionService $deletionService)
    {
        $deletionService->deletePartner($partner);

        return redirect()->route('admin.partners.index')
            ->with('success', 'Partner and all linked data deleted successfully.');
    }

    public function bulkDestroy(Request $request, CascadeEntityDeletionService $deletionService)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:partners,id'],
        ]);

        $partners = Partner::query()->whereIn('id', $validated['ids'])->get();
        \Illuminate\Support\Facades\DB::transaction(function () use ($partners, $deletionService) {
            foreach ($partners as $partner) {
                $deletionService->deletePartner($partner);
            }
        });

        return back()->with('success', $partners->count().' partner(s) and all linked data deleted.');
    }
}
