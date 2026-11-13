<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;

class SecurityEventController extends Controller
{
    use SearchEscaping;

    public function index(Request $request)
    {
        $query = SecurityEvent::with('user');

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->string('search'));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw($this->searchLikeSql('event_type'), ["%{$search}%"])
                    ->orWhereRaw($this->searchLikeSql('description'), ["%{$search}%"])
                    ->orWhereRaw($this->searchLikeSql('ip_address'), ["%{$search}%"])
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->whereRaw($this->searchLikeSql('name'), ["%{$search}%"])
                        ->orWhereRaw($this->searchLikeSql('email'), ["%{$search}%"]));
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('is_resolved', $request->status === 'resolved');
        }

        $events = $query->latest('detected_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $summary = [
            'open' => SecurityEvent::where('is_resolved', false)->count(),
            'critical' => SecurityEvent::where('is_resolved', false)->where('severity', 'critical')->count(),
            'resolved_today' => SecurityEvent::where('is_resolved', true)->whereDate('resolved_at', today())->count(),
        ];

        return view('admin.security-events.index', compact('events', 'summary'));
    }

    public function show(SecurityEvent $securityEvent)
    {
        $securityEvent->load('user');

        return view('admin.security-events.show', compact('securityEvent'));
    }

    public function update(Request $request, SecurityEvent $securityEvent)
    {
        $validated = $request->validate([
            'is_resolved' => 'required|boolean',
            'resolution_notes' => 'required_if:is_resolved,1|nullable|string|max:5000',
        ]);

        $resolved = $request->boolean('is_resolved');
        $securityEvent->update([
            'is_resolved' => $resolved,
            'resolved_at' => $resolved ? now() : null,
            'resolved_by' => $resolved ? auth()->user()->email : null,
            'resolution_notes' => $resolved ? $validated['resolution_notes'] : null,
        ]);

        return redirect()->route('admin.security-events.show', $securityEvent)
            ->with('success', $resolved ? 'Security event resolved.' : 'Security event reopened.');
    }
}
