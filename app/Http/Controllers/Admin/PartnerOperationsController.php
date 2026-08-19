<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Partner;
use App\Models\PartnerStaff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerOperationsController extends Controller
{
    public function menu(Partner $partner)
    {
        $partner->load(['menuCategories.items']);

        return view('admin.partners.menu', compact('partner'));
    }

    public function storeCategory(Request $request, Partner $partner)
    {
        $validated = $this->validateCategory($request);
        $partner->menuCategories()->create($validated);

        return back()->with('success', 'Menu category created.');
    }

    public function updateCategory(Request $request, Partner $partner, MenuCategory $category)
    {
        $this->ensureOwnedByPartner($partner, $category->partner_id);
        $category->update($this->validateCategory($request));

        return back()->with('success', 'Menu category updated.');
    }

    public function destroyCategory(Partner $partner, MenuCategory $category)
    {
        $this->ensureOwnedByPartner($partner, $category->partner_id);
        $category->delete();

        return back()->with('success', 'Menu category and its items deleted.');
    }

    public function storeItem(Request $request, Partner $partner)
    {
        $validated = $this->validateItem($request, $partner);
        $partner->menuItems()->create($validated);

        return back()->with('success', 'Menu item created.');
    }

    public function updateItem(Request $request, Partner $partner, MenuItem $item)
    {
        $this->ensureOwnedByPartner($partner, $item->partner_id);
        $item->update($this->validateItem($request, $partner));

        return back()->with('success', 'Menu item updated.');
    }

    public function destroyItem(Partner $partner, MenuItem $item)
    {
        $this->ensureOwnedByPartner($partner, $item->partner_id);
        $item->delete();

        return back()->with('success', 'Menu item deleted.');
    }

    public function staff(Partner $partner)
    {
        $partner->load(['staff.user', 'staff.inviter']);
        $assignedUserIds = $partner->staff->pluck('user_id');
        $users = User::where('is_active', true)
            ->whereNotIn('id', $assignedUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.partners.staff', compact('partner', 'users'));
    }

    public function storeStaff(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('partner_staff')->where('partner_id', $partner->id),
            ],
            'role' => 'required|in:admin,cook,cashier',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:orders,menu,reports,staff',
        ]);

        $partner->staff()->create([
            ...$validated,
            'permissions' => $validated['permissions'] ?? [],
            'is_active' => true,
            'invited_by' => auth()->id(),
        ]);

        return back()->with('success', 'Staff member added.');
    }

    public function updateStaff(Request $request, Partner $partner, PartnerStaff $staff)
    {
        $this->ensureOwnedByPartner($partner, $staff->partner_id);
        $validated = $request->validate([
            'role' => 'required|in:admin,cook,cashier',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:orders,menu,reports,staff',
            'is_active' => 'nullable|boolean',
        ]);

        $staff->update([
            ...$validated,
            'permissions' => $validated['permissions'] ?? [],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Staff access updated.');
    }

    public function destroyStaff(Partner $partner, PartnerStaff $staff)
    {
        $this->ensureOwnedByPartner($partner, $staff->partner_id);
        $staff->delete();

        return back()->with('success', 'Staff member removed.');
    }

    private function validateCategory(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon_emoji' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
        ]);

        return [
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'is_popular' => $request->boolean('is_popular'),
        ];
    }

    private function validateItem(Request $request, Partner $partner): array
    {
        $validated = $request->validate([
            'menu_category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:2048',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'tags' => 'nullable|string|max:1000',
            'options' => 'nullable|json',
            'is_available' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_best_seller' => 'nullable|boolean',
            'requires_prescription' => 'nullable|boolean',
        ]);

        $category = MenuCategory::findOrFail($validated['menu_category_id']);
        $this->ensureOwnedByPartner($partner, $category->partner_id);

        return [
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'tags' => filled($validated['tags'] ?? null)
                ? array_values(array_filter(array_map('trim', explode(',', $validated['tags']))))
                : null,
            'options' => filled($validated['options'] ?? null)
                ? json_decode($validated['options'], true, 512, JSON_THROW_ON_ERROR)
                : null,
            'is_available' => $request->boolean('is_available'),
            'is_popular' => $request->boolean('is_popular'),
            'is_featured' => $request->boolean('is_featured'),
            'is_best_seller' => $request->boolean('is_best_seller'),
            'requires_prescription' => $request->boolean('requires_prescription'),
        ];
    }

    private function ensureOwnedByPartner(Partner $partner, int $partnerId): void
    {
        abort_unless($partner->id === $partnerId, 404);
    }
}
