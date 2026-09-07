<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MissionTemplate;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;

class MissionTemplateController extends Controller
{
    use SearchEscaping;

    public function index(Request $request)
    {
        $query = MissionTemplate::query();

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $templates = $query->latest()->paginate(20)->withQueryString();
        return view('admin.mission-templates.index', compact('templates'));
    }

    public function show(MissionTemplate $missionTemplate)
    {
        return view('admin.mission-templates.show', compact('missionTemplate'));
    }

    public function create()
    {
        return view('admin.mission-templates.create');
    }

    private const ALLOWED_GOAL_TYPES = ['complete_orders','specific_service','get_rating','refer_friend','leseat_order','lesride_orders','friend_referral','app_review','social_follow'];
    private const ALLOWED_CURRENCIES = ['PHP'];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,weekly,monthly,one_time',
            'target_audience' => 'required|in:driver,merchant,customer',
            'goal_type' => ['required','string','max:50','in:'.implode(',', self::ALLOWED_GOAL_TYPES)],
            'goal_target' => 'required|integer|min:1',
            'reward_amount' => 'required|numeric|min:0',
            'reward_currency' => ['nullable','string','max:10','in:'.implode(',', self::ALLOWED_CURRENCIES)],
            'service_code' => 'nullable|string|max:50|exists:services,code',
            'is_active' => 'boolean',
        ]);

        if ($validated['goal_type'] === 'specific_service' && empty($validated['service_code'])) {
            return back()->withErrors(['service_code' => 'service_code is required when goal_type is specific_service'])->withInput();
        }

        $validated['reward_currency'] = $validated['reward_currency'] ?? 'PHP';
        $validated['is_active'] = $request->boolean('is_active', true);

        MissionTemplate::create($validated);

        return redirect()->route('admin.mission-templates.index')->with('success', 'Mission created successfully.');
    }

    public function edit(MissionTemplate $missionTemplate)
    {
        return view('admin.mission-templates.edit', compact('missionTemplate'));
    }

    public function update(Request $request, MissionTemplate $missionTemplate)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,weekly,monthly,one_time',
            'target_audience' => 'required|in:driver,merchant,customer',
            'goal_type' => ['required','string','max:50','in:'.implode(',', self::ALLOWED_GOAL_TYPES)],
            'goal_target' => 'required|integer|min:1',
            'reward_amount' => 'required|numeric|min:0',
            'reward_currency' => ['nullable','string','max:10','in:'.implode(',', self::ALLOWED_CURRENCIES)],
            'service_code' => 'nullable|string|max:50|exists:services,code',
            'is_active' => 'boolean',
        ]);

        if ($validated['goal_type'] === 'specific_service' && empty($validated['service_code'])) {
            return back()->withErrors(['service_code' => 'service_code is required when goal_type is specific_service'])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active');

        $missionTemplate->update($validated);

        return redirect()->route('admin.mission-templates.show', $missionTemplate)->with('success', 'Mission updated successfully.');
    }

    public function destroy(MissionTemplate $missionTemplate)
    {
        $missionTemplate->delete();
        return redirect()->route('admin.mission-templates.index')->with('success', 'Mission deleted successfully.');
    }

    public function toggleStatus(MissionTemplate $missionTemplate)
    {
        $missionTemplate->update(['is_active' => !$missionTemplate->is_active]);
        $status = $missionTemplate->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Mission {$status} successfully.");
    }
}
