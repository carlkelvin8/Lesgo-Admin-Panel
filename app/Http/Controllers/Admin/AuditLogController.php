<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use SearchEscaping;

    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw($this->searchLikeSql('action'), ["%{$search}%"])
                    ->orWhereRaw($this->searchLikeSql('resource_type'), ["%{$search}%"])
                    ->orWhereRaw($this->searchLikeSql('ip_address'), ["%{$search}%"])
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->whereRaw($this->searchLikeSql('name'), ["%{$search}%"])
                            ->orWhereRaw($this->searchLikeSql('email'), ["%{$search}%"]);
                    });
            });
        }

        if ($request->filled('event_category')) {
            $query->where('event_category', $request->event_category);
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->filled('is_suspicious')) {
            $query->where('is_suspicious', $request->is_suspicious === 'yes');
        }

        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to.' 23:59:59');
        }

        $logs = $query->latest('occurred_at')->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
